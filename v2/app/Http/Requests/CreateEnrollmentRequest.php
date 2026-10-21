<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CreateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('enrollments.manage') ?? false; }
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('card_number'))) { $this->merge(['card_number' => Str::upper(trim($this->input('card_number')))]); }
    }
    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'max:50', 'exists:students,card_number'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'study_level_id' => ['required', 'integer', 'exists:study_levels,id'],
            'enrolled_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount_due' => ['prohibited'], 'student_id' => ['prohibited'], 'eligibility' => ['prohibited'],
        ];
    }
    public function messages(): array
    {
        return ['card_number.exists' => 'Aucun dossier étudiant ne correspond à ce numéro de carte.', 'enrolled_on.before_or_equal' => 'La date d’inscription ne peut pas être future.'];
    }
}
