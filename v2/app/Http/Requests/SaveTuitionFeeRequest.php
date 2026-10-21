<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveTuitionFeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('schooling.manage') ?? false; }
    public function rules(): array
    {
        return [
            'academic_year_id' => $this->isMethod('POST') ? ['required', 'integer', 'exists:academic_years,id'] : ['prohibited'],
            'study_level_id' => $this->isMethod('POST') ? ['required', 'integer', 'exists:study_levels,id'] : ['prohibited'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'revision' => $this->isMethod('POST') ? ['prohibited'] : ['required', 'integer', 'min:1'],
        ];
    }
    public function messages(): array
    {
        return ['amount.integer' => 'Saisissez un montant entier en FCFA.', 'amount.min' => 'Le tarif doit être strictement positif.'];
    }
}
