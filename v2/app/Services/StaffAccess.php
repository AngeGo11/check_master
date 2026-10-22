<?php
namespace App\Services;
use App\Models\{StaffProfile, User, UserGroup};
use Illuminate\Support\Facades\DB;
class StaffAccess
{
    public function canView(User $user): bool { return $user->can('hr.view') && $user->belongsToCompatibleGroup(UserGroup::ADMINISTRATOR); }
    public function canManage(User $user): bool { return $this->canView($user) && $user->can('hr.manage'); }
    public function canCatalog(User $user): bool { return $this->canView($user) && $user->can('hr.catalogs.manage'); }
    public function compatible(User $user, string $kind): bool { return $user->types()->whereIn('user_types.id', $kind === 'teacher' ? [2, 3] : [4])->exists(); }
    public function own(User $user): ?StaffProfile
    {
        abort_unless($user->can('hr.self') && $user->types()->whereIn('user_types.id', [2, 3, 4])->exists(), 403);
        $profile = StaffProfile::where('user_id', $user->id)->first(); return $profile && $this->compatible($user, $profile->kind) ? $profile : null;
    }
    public static function revision(StaffProfile|\App\Models\StaffReference $record, int $submitted): void
    {
        if ($record->revision !== $submitted) { throw \Illuminate\Validation\ValidationException::withMessages(['revision' => 'Cette fiche a changé. Rechargez-la avant de réessayer.']); }
    }
    public static function audit(User $actor, string $action, ?int $profileId = null, ?int $referenceId = null, array $metadata = []): void
    {
        DB::table('staff_audits')->insert(['actor_user_id' => $actor->id, 'staff_profile_id' => $profileId, 'staff_reference_id' => $referenceId, 'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
