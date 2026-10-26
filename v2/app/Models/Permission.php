<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const CODES = ['users.view', 'users.manage', 'groups.view', 'groups.manage', 'students.view', 'students.manage', 'students.self', 'academic.view', 'academic.manage', 'schooling.view', 'schooling.manage', 'enrollments.view', 'enrollments.manage', 'payments.manage', 'eligibility.review', 'reports.view', 'reports.self', 'internships.self', 'reports.forward', 'reports.assigned', 'reports.vote', 'messages.use', 'messages.broadcast', 'meetings.view', 'meetings.manage', 'hr.view', 'hr.manage', 'hr.catalogs.manage', 'hr.self', 'grades.view', 'grades.manage', 'grades.self', 'grades.catalogs.manage', 'defenses.view', 'defenses.manage', 'defenses.self', 'defenses.assigned', 'defenses.assess', 'claims.self', 'claims.view', 'claims.manage', 'claims.assigned', 'audit.view', 'audit.export', 'progression.view', 'progression.manage', 'progression.self', 'progression.fees', 'documents.self', 'documents.minutes', 'documents.view', 'documents.archive'];

    public static function allowedForGroup(string $permission, string $group): bool
    {
        if ($permission === 'documents.self') { return $group === 'student'; }
        if ($permission === 'documents.minutes') { return $group === 'validation-committee'; }
        if ($permission === 'documents.view') { return in_array($group, ['communications', 'schooling', UserGroup::ADMINISTRATOR], true); }
        if (in_array($permission, ['progression.view', 'progression.manage'], true)) { return in_array($group, ['schooling', UserGroup::ADMINISTRATOR], true); }
        if ($permission === 'progression.self') { return $group === 'student'; }
        if ($permission === 'progression.fees') { return $group === UserGroup::ADMINISTRATOR; }
        if (in_array($permission, ['audit.view', 'audit.export'], true)) { return $group === UserGroup::ADMINISTRATOR; }
        if ($permission === 'claims.self') { return $group === 'student'; }
        if ($permission === 'claims.assigned') { return $group === 'program-manager'; }
        if (in_array($permission, ['claims.view', 'claims.manage'], true)) { return in_array($group, ['schooling', UserGroup::ADMINISTRATOR], true); }
        if (in_array($permission, ['grades.view', 'grades.manage', 'defenses.view', 'defenses.manage'], true)) { return in_array($group, ['schooling', UserGroup::ADMINISTRATOR], true); }
        if ($permission === 'grades.catalogs.manage') { return $group === UserGroup::ADMINISTRATOR; }
        if (in_array($permission, ['grades.self', 'defenses.self'], true)) { return $group === 'student'; }
        if (in_array($permission, ['defenses.assigned', 'defenses.assess'], true)) { return in_array($group, ['teacher', 'level-manager', 'program-manager', 'validation-committee'], true); }
        if (in_array($permission, ['hr.view', 'hr.manage', 'hr.catalogs.manage'], true)) { return $group === UserGroup::ADMINISTRATOR; }
        if ($permission === 'hr.self') { return $group !== 'student'; }
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
