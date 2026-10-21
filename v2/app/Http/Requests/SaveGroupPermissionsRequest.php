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
            foreach (['users', 'groups', 'students', 'academic'] as $resource) {
                $permissions = $this->input('permissions', []);
                if (in_array($resource.'.manage', $permissions, true) && ! in_array($resource.'.view', $permissions, true)) {
                    $validator->errors()->add('permissions', 'Le droit de consultation correspondant est nécessaire pour accorder la gestion.');
                }
            }
        }];
    }
}
