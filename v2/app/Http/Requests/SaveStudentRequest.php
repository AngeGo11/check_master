<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveStudentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('students.manage') ?? false; }

    protected function prepareForValidation(): void
    {
        foreach (['last_name', 'first_name', 'card_number', 'email'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => match ($field) {
                    'card_number' => Str::upper($value), 'email' => Str::lower($value), default => $value,
                }]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9._\/-]*$/', Rule::unique('students')->ignore($this->route('student'))],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('students')->ignore($this->route('student'))],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['Homme', 'Femme'])],
            'study_level_id' => ['required', 'integer', 'exists:study_levels,id'],
            'promotion_id' => ['required', 'integer', 'exists:promotions,id'],
            'student_status_id' => ['required', 'integer', 'exists:student_statuses,id'],
            'is_archived' => ['required', 'boolean'],
            'revision' => $this->isMethod('POST') ? ['prohibited'] : ['required', 'integer', 'min:1'],
            'user_id' => ['prohibited'], 'eligibility' => ['prohibited'], 'password' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_number.required' => 'Indiquez le numéro de carte.',
            'card_number.unique' => 'Ce numéro de carte existe déjà, y compris dans les dossiers archivés.',
            'card_number.regex' => 'Utilisez des lettres, chiffres, tirets, points, barres obliques ou traits de soulignement.',
            'email.unique' => 'Cette adresse e-mail appartient déjà à un dossier étudiant.',
            'email.email' => 'Indiquez une adresse e-mail valide.',
            'last_name.required' => 'Indiquez le nom.', 'first_name.required' => 'Indiquez les prénoms.',
            'date_of_birth.before_or_equal' => 'La date de naissance ne peut pas être future.',
            '*.exists' => 'Choisissez une valeur du catalogue.',
            'user_id.prohibited' => 'Utilisez le rattachement de compte depuis la fiche étudiant.',
        ];
    }
}
