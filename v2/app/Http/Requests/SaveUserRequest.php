<?php

namespace App\Http\Requests;

use App\Models\UserGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'is_active' => ['required', 'boolean'],
            'type_ids' => ['required', 'array', 'min:1', 'max:4'],
            'type_ids.*' => ['required', 'integer', 'distinct', 'exists:user_types,id'],
            'group_ids' => ['present', 'array', 'max:9'],
            'group_ids.*' => ['required', 'integer', 'distinct', 'exists:user_groups,id'],
            'password' => $this->isMethod('POST') ? ['required', 'string', 'min:12', 'max:255', 'confirmed'] : ['prohibited'],
            'password_confirmation' => $this->isMethod('POST') ? ['required', 'string', 'max:255'] : ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $groups = $this->input('group_ids', []);
            $eligible = UserGroup::whereIn('id', $groups)->whereHas('types',
                fn ($query) => $query->whereIn('user_types.id', $this->input('type_ids')))->count();
            if ($eligible !== count($groups)) {
                $validator->errors()->add('group_ids', 'Chaque groupe doit correspondre à au moins un type de l’utilisateur.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indiquez le nom complet.',
            'email.email' => 'Indiquez une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'type_ids.required' => 'Sélectionnez au moins un type d’utilisateur.',
            'type_ids.min' => 'Sélectionnez au moins un type d’utilisateur.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
