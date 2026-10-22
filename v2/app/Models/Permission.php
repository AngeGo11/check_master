<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const CODES = ['users.view', 'users.manage', 'groups.view', 'groups.manage', 'students.view', 'students.manage', 'students.self', 'academic.view', 'academic.manage', 'schooling.view', 'schooling.manage', 'enrollments.view', 'enrollments.manage', 'payments.manage', 'eligibility.review', 'reports.view', 'reports.self', 'internships.self', 'reports.forward', 'reports.assigned', 'reports.vote', 'messages.use', 'messages.broadcast', 'meetings.view', 'meetings.manage'];

    public static function allowedForGroup(string $permission, string $group): bool
    {
        $restricted = ['internships.self' => 'student', 'reports.forward' => 'communications', 'reports.assigned' => 'validation-committee', 'reports.vote' => 'validation-committee'];
        if ($permission === 'meetings.manage') { return $group === 'validation-committee'; }
        if ($permission === 'meetings.view') { return in_array($group, ['validation-committee', UserGroup::ADMINISTRATOR], true); }
        if ($permission === 'messages.broadcast') { return in_array($group, ['communications', 'validation-committee', UserGroup::ADMINISTRATOR], true); }
        return ! isset($restricted[$permission]) || $restricted[$permission] === $group;
    }

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
