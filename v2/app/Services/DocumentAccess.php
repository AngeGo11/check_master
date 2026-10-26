<?php
namespace App\Services;
use App\Models\{PersonalArchive, ReportRound, ReportVersion, User, WorkDocument, WorkDocumentEntry, WorkDocumentVersion};
use Illuminate\Database\Eloquent\Builder;
class DocumentAccess
{
    public function personal(User $u): bool { return $u->can('documents.self') && $u->can('reports.self') && $u->belongsToCompatibleGroup('student'); }
    public function writer(User $u): bool { return $u->can('documents.minutes') && app(ReportAccess::class)->canReadAssigned($u); }
    public function reader(User $u): bool { return $u->can('documents.view') && $u->can('reports.view') && collect(['communications', 'schooling', 'platform-administrator'])->contains(fn ($g) => $u->belongsToCompatibleGroup($g)); }
    public function archive(User $u): bool { return $u->can('documents.archive'); }
    public function enter(User $u): bool { return $this->personal($u) || $this->writer($u) || $this->reader($u); }
    public function reportVersions(User $u): Builder
    {
        if ($u->can('reports.view')) { return ReportVersion::query(); }
        return ReportVersion::where(function ($q) use ($u) {
            $q->whereRaw('1 = 0');
            if (app(ReportAccess::class)->canReadAssigned($u)) { $q->orWhereHas('rounds.members', fn ($q) => $q->where('user_id', $u->id)); }
            if ($u->can('reports.self') && $u->belongsToCompatibleGroup('student')) { $q->orWhereHas('report.student', fn ($q) => $q->where('user_id', $u->id)); }
        });
    }
    public function fullVersions(User $u): Builder
    {
        return WorkDocumentVersion::where(function ($q) use ($u) {
            $q->whereRaw('1 = 0');
            if ($this->personal($u)) {
                $q->orWhereHas('document', fn ($q) => $q->where('kind', 'personal')->where('owner_user_id', $u->id)->whereHas('student', fn ($q) => $q->where('user_id', $u->id)));
            }
            if ($this->writer($u) || $this->reader($u)) {
                $q->orWhere(function ($q) use ($u) {
                    $q->whereHas('document', fn ($q) => $q->where('kind', 'minutes'))->whereHas('entries')
                        ->whereDoesntHave('entries', fn ($q) => $q->whereNotIn('report_version_id', $this->reportVersions($u)->select('id')))
                        ->where(function ($q) use ($u) {
                            $q->whereNotNull('published_at');
                            if ($this->writer($u)) { $q->orWhereHas('document', fn ($q) => $q->where('owner_user_id', $u->id)); }
                        });
                });
            }
        });
    }
    public function ownEntries(User $u): Builder
    {
        return WorkDocumentEntry::whereHas('version', fn ($q) => $q->whereNotNull('published_at'))
            ->whereHas('reportVersion.report.student', fn ($q) => $q->where('user_id', $u->id))
            ->when(! $this->personal($u), fn ($q) => $q->whereRaw('1 = 0'));
    }
    public function editable(User $u, WorkDocument $d): bool
    {
        if ((int) $d->owner_user_id !== $u->id) { return false; }
        if ($d->kind === 'personal') { return $this->personal($u) && (int) $d->student?->user_id === $u->id && ! $d->student->is_archived; }
        if (! $this->writer($u)) { return false; }
        return $d->latestVersion && ! $d->latestVersion->entries()->whereNotIn('report_round_id', $this->rounds($u)->select('id'))->exists();
    }
    public function rounds(User $u): Builder
    {
        return ReportRound::whereIn('status', ['approved', 'changes_requested'])->whereNotNull('closed_at')
            ->whereHas('members', fn ($q) => $q->where('user_id', $u->id))
            ->whereHas('version', fn ($q) => $q->where('submitted_by', '!=', $u->id)->whereIn('id', $this->reportVersions($u)->select('id'))
                ->whereHas('report.student', fn ($q) => $q->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $u->id))))
            ->when(! $this->writer($u), fn ($q) => $q->whereRaw('1 = 0'));
    }
    public function archives(User $u): Builder
    {
        abort_unless($this->archive($u), 403);
        return PersonalArchive::where('user_id', $u->id)->where(function ($q) use ($u) {
            $q->whereIn('report_version_id', $this->reportVersions($u)->select('id'))
                ->orWhere(fn ($q) => $q->whereNull('work_document_entry_id')->whereIn('work_document_version_id', $this->fullVersions($u)->select('id')))
                ->orWhereIn('work_document_entry_id', $this->ownEntries($u)->select('id'));
        });
    }
    public function sourceSnapshot(ReportRound $r): array
    {
        $r->loadMissing(['version.report.student', 'version.enrollment.academicYear', 'members.vote']); $v = $r->version; $s = $v->report->student;
        return ['report_reference' => $v->report->reference, 'report_title' => $v->title, 'report_number' => $v->number, 'student' => $s->last_name.' '.$s->first_name,
            'card_number' => $s->card_number, 'year' => $v->enrollment->academicYear->name, 'round_number' => $r->number, 'status' => $r->status,
            'closed_at' => $r->closed_at?->toIso8601String(), 'members' => $r->members->count(), 'approved' => $r->members->filter(fn ($m) => $m->vote?->decision === 'approved')->count()];
    }
    public function sourceFingerprint(ReportRound $r): string
    {
        $r->loadMissing('members.vote');
        return hash('sha256', json_encode([$r->id, $r->report_version_id, $r->status, $r->members->sortBy('id')->map(fn ($m) => [$m->id, $m->user_id, $m->vote?->id, $m->vote?->decision])->values()->all()], JSON_THROW_ON_ERROR));
    }
}
