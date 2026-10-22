<?php
namespace App\Services;

use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReportAccess
{
    public function canForward(User $user): bool
    {
        return $user->belongsToCompatibleGroup('communications') && $user->can('reports.forward') && $user->can('reports.view');
    }
    public function canVote(User $user): bool
    {
        return $user->belongsToCompatibleGroup('validation-committee') && $user->can('reports.vote') && $user->can('reports.assigned');
    }
    public function canReadAssigned(User $user): bool
    {
        return $user->belongsToCompatibleGroup('validation-committee') && $user->can('reports.assigned');
    }
    public function reports(User $user): Builder
    {
        $query = Report::query();
        if ($user->can('reports.view')) { return $query; }
        abort_unless($this->canReadAssigned($user), 403);
        // A new deposit stays private to the student/communication until that version is forwarded.
        return $query->whereHas('latestVersion.rounds.members', fn ($q) => $q->where('user_id', $user->id));
    }
    public function versionAllowed(User $user, ReportVersion $version): bool
    {
        return $user->can('reports.view') || ($this->canReadAssigned($user) && $version->rounds()->whereHas('members', fn ($q) => $q->where('user_id', $user->id))->exists());
    }
    public function roster(): Collection
    {
        // All active, compatible members; never a client-selected subset. Temporary passwords
        // do not remove a member from the required approvals.
        return User::where('is_active', true)->whereHas('groups', fn ($q) => $q->where('code', 'validation-committee'))
            ->whereHas('types', fn ($q) => $q->whereIn('user_types.id', [2, 3]))->orderBy('id')->get();
    }
    public function fingerprint(array $ids): string
    {
        $ids = array_map('intval', $ids); sort($ids, SORT_NUMERIC);
        return hash('sha256', json_encode($ids, JSON_THROW_ON_ERROR));
    }
}
