<?php
namespace App\Services;
use App\Models\{InternalMessage, Meeting, User, UserGroup};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CollaborationAccess
{
    public const TIMEZONE = 'Africa/Abidjan';
    // Recipients may have a temporary password; it is required before they can read or act.
    public function usersWith(string $permission): Builder
    {
        return User::where('is_active', true)->whereExists(function ($q) use ($permission) {
            $q->selectRaw('1')->from('group_user as member')
                ->join('group_permission as grant', 'grant.user_group_id', '=', 'member.user_group_id')
                ->join('user_group_user_type as compatible', 'compatible.user_group_id', '=', 'member.user_group_id')
                ->join('type_user as type', function ($join) { $join->on('type.user_id', '=', 'member.user_id')->on('type.user_type_id', '=', 'compatible.user_type_id'); })
                ->whereColumn('member.user_id', 'users.id')->where('grant.permission_code', $permission);
        });
    }
    public function compatibleGroup(Builder $query, int $group): Builder
    {
        return $query->whereHas('groups', fn ($q) => $q->where('user_groups.id', $group))
            ->whereHas('types', fn ($q) => $q->whereIn('user_types.id', DB::table('user_group_user_type')->where('user_group_id', $group)->select('user_type_id')));
    }
    public function members(): Builder { return $this->compatibleGroup($this->usersWith('meetings.view'), 9)->orderBy('name')->orderBy('id'); }
    public function canOrganize(User $user): bool { return $user->can('meetings.manage') && $user->can('meetings.view') && $user->belongsToCompatibleGroup('validation-committee'); }
    public function canManage(User $user, Meeting $meeting): bool { return $this->canOrganize($user) && (int) $meeting->created_by === $user->id; }
    public function meetings(User $user): Builder
    {
        abort_unless($user->can('meetings.view'), 403);
        $q = Meeting::query();
        if ($user->belongsToCompatibleGroup(UserGroup::ADMINISTRATOR)) { return $q; }
        abort_unless($user->belongsToCompatibleGroup('validation-committee'), 403);
        return $q->where(fn ($q) => $q->where('created_by', $user->id)->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id)->whereNull('removed_at')));
    }
    public function messages(User $user): Builder
    {
        abort_unless($user->can('messages.use'), 403);
        return InternalMessage::where(fn ($q) => $q->where('sender_id', $user->id)
            ->orWhere(fn ($q) => $q->where('status', 'sent')->whereHas('recipients', fn ($p) => $p->where('user_id', $user->id))));
    }
    public function unread(User $user): int
    {
        return $user->can('messages.use') ? $this->messages($user)->where('status', 'sent')
            ->whereHas('recipients', fn ($q) => $q->where('user_id', $user->id)->where('folder', 'inbox')->whereNull('read_at'))->count() : 0;
    }
}
