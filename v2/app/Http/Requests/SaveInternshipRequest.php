<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveInternshipRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('enrollments.manage') ?? false; }
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:200'], 'title' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'evidence_reference' => ['required', 'string', 'max:255'], 'revision' => ['required', 'integer', 'min:1'],
        ];
    }
    public function messages(): array
    {
        return ['ends_on.after_or_equal' => 'La fin du stage doit suivre son début.', 'evidence_reference.required' => 'Indiquez la référence de la déclaration ou de la convention vérifiée.'];
    }
}
