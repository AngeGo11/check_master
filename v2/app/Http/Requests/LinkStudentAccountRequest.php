<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LinkStudentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('students.manage') ?? false) && $this->user()->can('users.manage');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('account_email'))) {
            $this->merge(['account_email' => Str::lower(trim($this->input('account_email')))]);
        }
    }

    public function rules(): array
    {
        return ['account_email' => ['present', 'nullable', 'string', 'email', 'max:254'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public function messages(): array
    {
        return ['account_email.email' => 'Indiquez l’adresse e-mail du compte à rattacher.'];
    }
}
