<?php

namespace App\Services;

use App\Models\{GradePublication, StudentClaim, User, UserGroup};
use Illuminate\Database\Eloquent\Builder;

class ClaimAccess
{
    public function personal(User $u): bool { return $u->can('claims.self') && $u->belongsToCompatibleGroup('student'); }
    public function global(User $u): bool { return $u->can('claims.view') && ($u->belongsToCompatibleGroup('schooling') || $u->belongsToCompatibleGroup(UserGroup::ADMINISTRATOR)); }
    public function assigned(User $u): bool { return $u->can('claims.assigned') && $u->belongsToCompatibleGroup('program-manager'); }
    public function staff(User $u): bool { return $this->global($u) && $u->can('claims.manage'); }
    public function own(User $u, StudentClaim $c): bool { return $this->personal($u) && (int) $c->enrollment->student->user_id === $u->id; }
    public function conflict(User $u, StudentClaim $c): bool { return (int) $c->submitted_by === $u->id || (int) $c->enrollment->student->user_id === $u->id; }

    // Explicit views prevent an admin/student account from mixing personal and service lists.
    public function query(User $u, string $space): Builder
    {
        $q = StudentClaim::query();
        if ($space === 'personal') {
            abort_unless($this->personal($u), 403);
            return $q->whereHas('enrollment.student', fn ($q) => $q->where('user_id', $u->id));
        }
        abort_unless(in_array($space, ['service', 'assigned'], true), 403);
        if ($space === 'service') { abort_unless($this->global($u), 403); return $q; }
        abort_unless($this->assigned($u), 403);
        return $q->where('assigned_to', $u->id)->where('submitted_by', '!=', $u->id)
            ->whereHas('enrollment.student', fn ($q) => $q->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $u->id)));
    }

    public function canView(User $u, StudentClaim $c): bool
    {
        return $this->global($u) || $this->own($u, $c) || ($this->assigned($u) && (int) $c->assigned_to === $u->id && ! $this->conflict($u, $c));
    }

    public function capabilities(User $u, StudentClaim $c): array
    {
        $staff = $this->staff($u) && ! $this->conflict($u, $c);
        $assigned = $this->assigned($u) && (int) $c->assigned_to === $u->id && ! $this->conflict($u, $c);
        return ['review' => $staff, 'escalate' => $staff, 'reopen' => $staff,
            'resolve' => $staff || $assigned, 'comment' => $this->own($u, $c) || $staff || $assigned, 'withdraw' => $this->own($u, $c)];
    }

    public function actions(User $u, StudentClaim $c): array
    {
        $state = ['review' => $c->status === 'submitted', 'escalate' => in_array($c->status, StudentClaim::OPEN, true),
            'reopen' => $c->status === 'resolved', 'resolve' => in_array($c->status, ['submitted', 'in_review'], true) || ($c->status === 'escalated' && (int) $c->assigned_to === $u->id && $this->assigned($u)),
            'comment' => in_array($c->status, StudentClaim::OPEN, true), 'withdraw' => $c->status === 'submitted'];
        return array_keys(array_filter($this->capabilities($u, $c), fn ($allowed, $key) => $allowed && $state[$key], ARRAY_FILTER_USE_BOTH));
    }

    public function assignees(StudentClaim $c)
    {
        return User::where('is_active', true)->where('must_change_password', false)->where('id', '!=', $c->submitted_by)
            ->when($c->enrollment->student->user_id, fn ($q, $id) => $q->where('id', '!=', $id))
            ->whereHas('groups', fn ($q) => $q->where('code', 'program-manager'))->orderBy('name')->orderBy('id')->get()
            ->filter(fn ($u) => $this->assigned($u))->values();
    }

    // Copy names/codes only from the immutable published version, never from a correction draft.
    public function subjects(GradePublication $publication): array
    {
        $subjects = [];
        foreach ($publication->result['units'] as $unit) {
            $key = 'ue:'.$unit['code']; $subjects[$key] = ['key' => $key, 'label' => 'UE '.$unit['code'].' · '.$unit['name']];
            foreach ($unit['subjects'] as $subject) {
                if ($subject['key'] !== $key) { $subjects[$subject['key']] = ['key' => $subject['key'], 'label' => 'ECUE '.$subject['code'].' · '.$subject['name']]; }
            }
        }
        return $subjects;
    }
}
