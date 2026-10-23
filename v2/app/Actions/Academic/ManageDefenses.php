<?php
namespace App\Actions\Academic;
use App\Models\{Defense, DefenseJuror, Meeting, Report, User};
use App\Services\{AcademicAccess, CollaborationAccess, CollaborationLock, DefenseAccess, GradeCalculator};
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class ManageDefenses
{
    public function request(User $actor, array $data): Defense
    {
        return CollaborationLock::run($actor, 'defenses.self', function (User $actor) use ($data) {
            $access = app(DefenseAccess::class); abort_unless($access->own($actor), 403);
            $report = Report::whereKey($data['report_id'])->whereHas('student', fn ($q) => $q->where('user_id', $actor->id))->lockForUpdate()->firstOrFail();
            $existing = Defense::where('operation_key', $data['operation_key'])->first();
            if ($existing) { if ((int) $existing->report_id === $report->id && (int) $existing->requested_by === $actor->id) { return $existing; } throw ValidationException::withMessages(['operation_key' => 'Cette référence a déjà été utilisée.']); }
            AcademicAccess::revision($report, (int) $data['report_revision']); $this->ready($report);
            if (Defense::where('report_id', $report->id)->exists()) { throw ValidationException::withMessages(['report_id' => 'Une demande existe déjà pour ce mémoire. Consultez son suivi.']); }
            $d = new Defense; $d->forceFill(['reference' => 'SOU-'.Str::ulid(), 'student_id' => $report->student_id, 'enrollment_id' => $report->enrollment_id, 'report_id' => $report->id, 'report_version_id' => $report->latestVersion->id, 'report_round_id' => $report->latestVersion->latestRound->id, 'requested_by' => $actor->id, 'requested_at' => now(), 'operation_key' => $data['operation_key']])->save();
            AcademicAccess::audit($actor, 'defense.requested', enrollment: $d->enrollment_id, defense: $d->id); return $d;
        });
    }
    public function schedule(User $actor, Defense $target, array $data): void
    {
        CollaborationLock::run($actor, 'defenses.manage', function (User $actor) use ($target, $data) {
            $access = app(DefenseAccess::class); abort_unless($access->canManage($actor), 403);
            $d = Defense::whereKey($target->id)->lockForUpdate()->firstOrFail(); AcademicAccess::revision($d, (int) $data['revision']); abort_if((int) $d->student->user_id === $actor->id, 403);
            if ($d->status === 'completed' || ($d->status === 'scheduled' && $d->starts_at->lte(now()))) { throw ValidationException::withMessages(['defense' => 'Une soutenance commencée ou terminée ne peut plus être reprogrammée.']); }
            $this->ready($d->report, $d); $ids = array_map(fn ($j) => (int) $j['user_id'], $data['jurors']);
            if (count(array_unique($ids)) !== count($ids) || count(array_filter($data['jurors'], fn ($j) => $j['role'] === 'president')) !== 1) { throw ValidationException::withMessages(['jurors' => 'Choisissez des membres distincts et exactement un président.']); }
            $members = $access->jurors()->whereIn('id', $ids)->get()->keyBy('id');
            if ($members->count() !== count($ids) || in_array((int) $d->student->user_id, $ids, true) || in_array((int) $d->requested_by, $ids, true)) { throw ValidationException::withMessages(['jurors' => 'Choisissez des comptes actifs de type enseignant/responsable, autorisés à consulter leur jury, sans le candidat.']); }
            $president = collect($data['jurors'])->firstWhere('role', 'president');
            if (! app(CollaborationAccess::class)->usersWith('defenses.assess')->whereKey($president['user_id'])->exists()) { throw ValidationException::withMessages(['jurors' => 'Le président doit disposer du droit d’enregistrer le résultat.']); }
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at'], CollaborationAccess::TIMEZONE)->utc(); $end = $start->addMinutes((int) $data['duration_minutes']);
            if ($start->lte(now())) { throw ValidationException::withMessages(['starts_at' => 'La soutenance doit commencer dans le futur.']); }
            $location = Str::squish($data['location']); $locationKey = Str::lower($location);
            $conflict = Defense::where('status', 'scheduled')->where('id', '!=', $d->id)->where('starts_at', '<', $end)->where('ends_at', '>', $start)
                ->where(fn ($q) => $q->where('location_key', $locationKey)->orWhere('student_id', $d->student_id)->orWhereHas('currentJurors', fn ($q) => $q->whereIn('user_id', $ids)))->exists();
            $meetingConflict = Meeting::whereNull('cancelled_at')->where('starts_at', '<', $end)->where('ends_at', '>', $start)
                ->whereHas('participants', fn ($q) => $q->whereIn('user_id', $ids)->whereNull('removed_at')->where('response', '!=', 'declined'))->exists();
            if ($conflict || $meetingConflict) { throw ValidationException::withMessages(['starts_at' => 'Le lieu ou un membre du jury est déjà occupé sur ce créneau. Choisissez un autre horaire ou lieu.']); }
            $before = [...$d->only('status', 'starts_at', 'ends_at', 'location', 'instructions'), 'jurors' => $d->currentJurors()->get(['user_id', 'role', 'name_snapshot'])->toArray()];
            $d->currentJurors()->update(['current_slot' => null, 'removed_at' => now()]);
            foreach ($data['jurors'] as $j) { $m = new DefenseJuror; $m->forceFill(['defense_id' => $d->id, 'user_id' => $j['user_id'], 'role' => $j['role'], 'name_snapshot' => $members[$j['user_id']]->name, 'current_slot' => 1, 'created_at' => now()])->save(); }
            $d->forceFill(['status' => 'scheduled', 'starts_at' => $start, 'ends_at' => $end, 'location' => $location, 'location_key' => $locationKey, 'instructions' => $data['instructions'] ?? null, 'revision' => $d->revision + 1])->save();
            AcademicAccess::audit($actor, 'defense.scheduled', ['before' => $before, 'reason' => $data['reason']], $d->enrollment_id, defense: $d->id);
        });
    }
    public function cancel(User $actor, Defense $target, array $data): void
    {
        $access = app(DefenseAccess::class); $permission = $access->canManage($actor) ? 'defenses.manage' : 'defenses.self';
        CollaborationLock::run($actor, $permission, function (User $actor) use ($target, $data, $access) {
            $d = Defense::whereKey($target->id)->lockForUpdate()->firstOrFail(); AcademicAccess::revision($d, (int) $data['revision']);
            $manager = $access->canManage($actor) && (int) $d->student->user_id !== $actor->id;
            abort_unless($manager || ($access->own($actor) && (int) $d->student->user_id === $actor->id && $d->status === 'requested'), 403);
            if (! in_array($d->status, ['requested', 'scheduled'], true)) { throw ValidationException::withMessages(['defense' => 'Cette demande est déjà annulée ou terminée.']); }
            $d->forceFill(['status' => 'cancelled', 'revision' => $d->revision + 1])->save(); AcademicAccess::audit($actor, 'defense.cancelled', ['reason' => $data['reason']], $d->enrollment_id, defense: $d->id);
        });
    }
    public function result(User $actor, Defense $target, array $data): void
    {
        CollaborationLock::run($actor, 'defenses.assess', function (User $actor) use ($target, $data) {
            $d = Defense::whereKey($target->id)->lockForUpdate()->firstOrFail(); abort_unless(app(DefenseAccess::class)->canAssess($actor, $d), 403); AcademicAccess::revision($d, (int) $data['revision']);
            if (! in_array($d->status, ['scheduled', 'completed'], true) || ! $d->ends_at || $d->ends_at->gt(now())) { throw ValidationException::withMessages(['defense' => 'Le résultat s’enregistre après la fin du créneau de soutenance.']); }
            $this->ready($d->report, $d);
            $score = GradeCalculator::score($data['score'] ?? null);
            if (($data['outcome'] === 'absent' && $score !== null) || ($data['outcome'] !== 'absent' && $score === null)) { throw ValidationException::withMessages(['score' => 'Renseignez la note du jury, sauf en cas d’absence.']); }
            $before = $d->only('outcome', 'score', 'result_comment', 'result_by', 'result_at'); $correction = $d->status === 'completed';
            $d->forceFill(['status' => 'completed', 'outcome' => $data['outcome'], 'score' => $score, 'result_comment' => $data['reason'], 'result_by' => $actor->id, 'result_at' => now(), 'revision' => $d->revision + 1])->save();
            AcademicAccess::audit($actor, $correction ? 'defense.result_corrected' : 'defense.result_recorded', ['before' => $before, 'reason' => $data['reason']], $d->enrollment_id, defense: $d->id);
        });
    }
    private function ready(Report $r, ?Defense $d = null): void
    {
        if (! app(DefenseAccess::class)->readiness($r)['ready'] || ($d && ((int) $d->report_version_id !== $r->latestVersion->id || (int) $d->report_round_id !== $r->latestVersion->latestRound->id))) { throw ValidationException::withMessages(['eligibility' => 'Le mémoire doit être accepté à l’unanimité, son PDF disponible et les critères d’éligibilité toujours réunis.']); }
    }
}
