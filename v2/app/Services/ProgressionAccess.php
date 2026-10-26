<?php
namespace App\Services;
use App\Models\{Enrollment, User};
class ProgressionAccess
{
    public function staff(User $u): bool { return $u->belongsToCompatibleGroup('schooling') || $u->belongsToCompatibleGroup('platform-administrator'); }
    public function view(User $u): bool { return $u->can('progression.view') && $this->staff($u); }
    public function manage(User $u): bool { return $this->view($u) && $u->can('progression.manage'); }
    public function personal(User $u): bool { return $u->can('progression.self') && $u->can('grades.self') && $u->belongsToCompatibleGroup('student'); }
    public function fees(User $u): bool { return $u->can('progression.fees') && $u->belongsToCompatibleGroup('platform-administrator'); }
    public function query(User $u, bool $personal)
    {
        abort_unless($personal ? $this->personal($u) : $this->view($u), 403);
        return Enrollment::query()->when($personal, fn ($q) => $q->whereHas('student', fn ($q) => $q->where('user_id', $u->id)));
    }
    public function canEdit(User $u, Enrollment $e): bool { return $this->manage($u) && ! $e->cancelled_at && ! $e->student->is_archived && (int) $e->student->user_id !== $u->id && $e->academicYear->status !== 'pending'; }
}
