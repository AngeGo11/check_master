<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePromotionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('academic.manage') ?? false; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('promotions')->ignore($this->route('promotion'))],
            'revision' => $this->isMethod('POST') ? ['prohibited'] : ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Indiquez le libellé de la promotion.', 'name.unique' => 'Cette promotion existe déjà.'];
    }
}
