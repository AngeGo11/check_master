<?php
namespace App\Services;
use App\Models\{Enrollment, User, UserGroup};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AcademicAccess
{
    public function staff(User $user): bool { return $user->belongsToCompatibleGroup('schooling') || $user->belongsToCompatibleGroup(UserGroup::ADMINISTRATOR); }
    public function canView(User $user): bool { return $user->can('grades.view') && $this->staff($user); }
    public function canManage(User $user): bool { return $this->canView($user) && $user->can('grades.manage'); }
    public function canCatalog(User $user): bool { return $this->canView($user) && $user->can('grades.catalogs.manage') && $user->belongsToCompatibleGroup(UserGroup::ADMINISTRATOR); }
    public function own(User $user, string $permission): void { abort_unless($user->can($permission) && $user->types()->where('user_types.id', 1)->exists(), 403); }
    public function editableEnrollment(Enrollment $e): void
    {
        if ($e->cancelled_at || $e->student->is_archived || $e->academicYear->status !== 'current') { throw ValidationException::withMessages(['enrollment' => 'Utilisez une inscription non annulée, un dossier non archivé et une année en cours.']); }
    }
    public static function revision(Model $record, int $revision): void { if ((int) $record->revision !== $revision) { throw ValidationException::withMessages(['revision' => 'Ce dossier a changé. Rechargez la page avant de réessayer.']); } }
    public static function audit(User $actor, string $action, array $metadata = [], ?int $enrollment = null, ?int $program = null, ?int $defense = null): void
    {
        DB::table('academic_audits')->insert(['actor_user_id' => $actor->id, 'enrollment_id' => $enrollment, 'teaching_program_id' => $program, 'defense_id' => $defense, 'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
