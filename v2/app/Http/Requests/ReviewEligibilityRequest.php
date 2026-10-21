<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewEligibilityRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('eligibility.review') ?? false; }
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:1000'], 'revision' => ['required', 'integer', 'min:1'],
            'result' => ['prohibited'], 'criteria' => ['prohibited'], 'eligible' => ['prohibited']];
    }
    public function messages(): array { return ['reason.required' => 'Ajoutez une observation pour cet examen.', 'result.prohibited' => 'Le résultat est déterminé par les critères du dossier.']; }
}
