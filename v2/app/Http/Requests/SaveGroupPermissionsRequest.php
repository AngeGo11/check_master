<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveGroupPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('groups.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array', 'max:'.count(Permission::CODES)],
            'permissions.*' => ['required', 'string', 'distinct', Rule::in(Permission::CODES)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach (['users', 'groups', 'students', 'academic', 'schooling', 'enrollments'] as $resource) {
                $permissions = $this->input('permissions', []);
                if (in_array($resource.'.manage', $permissions, true) && ! in_array($resource.'.view', $permissions, true)) {
                    $validator->errors()->add('permissions', 'Le droit de consultation correspondant est nécessaire pour accorder la gestion.');
                }
            }
            if (array_intersect(['payments.manage', 'eligibility.review'], $this->input('permissions', [])) && ! in_array('enrollments.view', $this->input('permissions', []), true)) {
                $validator->errors()->add('permissions', 'La consultation des inscriptions est nécessaire pour les règlements et l’examen d’éligibilité.');
            }
            $permissions = $this->input('permissions', []);
            foreach (['reports.forward' => 'reports.view', 'reports.vote' => 'reports.assigned', 'internships.self' => 'students.self', 'meetings.manage' => 'meetings.view', 'messages.broadcast' => 'messages.use', 'hr.manage' => 'hr.view', 'hr.catalogs.manage' => 'hr.view', 'grades.manage' => 'grades.view', 'grades.catalogs.manage' => 'grades.view', 'defenses.manage' => 'defenses.view', 'defenses.assess' => 'defenses.assigned'] as $write => $read) {
                if (in_array($write, $permissions, true) && ! in_array($read, $permissions, true)) { $validator->errors()->add('permissions', 'Le droit de consultation correspondant est nécessaire.'); }
            }
            foreach ($permissions as $permission) {
                if (! Permission::allowedForGroup($permission, $this->route('group')->code)) { $validator->errors()->add('permissions', 'Ce droit est réservé au groupe métier correspondant.'); }
            }
        }];
    }
}
