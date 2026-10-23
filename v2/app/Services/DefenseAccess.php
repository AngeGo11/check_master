<?php
namespace App\Services;
use App\Models\{Defense, Report, User};
use Illuminate\Database\Eloquent\Builder;
class DefenseAccess
{
    public function canView(User $u): bool { return $u->can('defenses.view') && app(AcademicAccess::class)->staff($u); }
    public function canManage(User $u): bool { return $this->canView($u) && $u->can('defenses.manage'); }
    public function assigned(User $u): bool { return $u->can('defenses.assigned') && $u->types()->whereIn('user_types.id', [2, 3])->exists(); }
    public function own(User $u): bool { return $u->can('defenses.self') && $u->types()->where('user_types.id', 1)->exists(); }
    public function visible(User $u): Builder
    {
        if ($this->canView($u)) { return Defense::query(); }
        $assigned = $this->assigned($u); $own = $this->own($u); abort_unless($assigned || $own, 403);
        return Defense::where(function ($q) use ($u, $assigned, $own) {
            $q->whereRaw('1=0');
            if ($assigned) { $q->orWhereHas('currentJurors', fn ($q) => $q->where('user_id', $u->id)); }
            if ($own) { $q->orWhereHas('student', fn ($q) => $q->where('user_id', $u->id)); }
        });
    }
    public function canAssess(User $u, Defense $d): bool
    {
        return $this->assigned($u) && $u->can('defenses.assess') && (int) $d->student->user_id !== $u->id
            && $d->currentJurors()->where('user_id', $u->id)->where('role', 'president')->exists();
    }
    public function jurors(): Builder { return app(CollaborationAccess::class)->usersWith('defenses.assigned')->whereHas('types', fn ($q) => $q->whereIn('user_types.id', [2, 3])); }
    public function readiness(Report $r): array
    {
        $version = $r->latestVersion; $round = $version?->latestRound;
        $approved = $r->status === 'accepted' && $round && $round->status === 'approved' && $round->members()->exists()
            && ! $round->members()->whereDoesntHave('vote', fn ($q) => $q->where('decision', 'approved'))->exists();
        $eligible = app(Eligibility::class)->evaluate($r->enrollment)['computed_result'] === 'eligible';
        $intact = $version && app(ReportFiles::class)->intact($version);
        return ['ready' => $approved && $eligible && $intact, 'approved' => (bool) $approved, 'eligible' => $eligible, 'document_available' => (bool) $intact];
    }
}
