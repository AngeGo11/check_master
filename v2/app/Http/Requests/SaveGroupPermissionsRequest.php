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
            if (in_array('reports.review', $this->input('permissions', []), true) && ! in_array('reports.view', $this->input('permissions', []), true)) {
                $validator->errors()->add('permissions', 'La consultation des mémoires est nécessaire pour les approuver ou demander une correction.');
            }
        }];
    }
}
