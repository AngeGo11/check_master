<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAcademicYearRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('schooling.manage') ?? false; }
    protected function prepareForValidation(): void { if (is_string($this->input('name'))) { $this->merge(['name' => trim($this->input('name'))]); } }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('academic_years')->ignore($this->route('year'))],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'status' => ['required', Rule::in(['pending', 'current', 'closed'])],
            'revision' => $this->isMethod('POST') ? ['prohibited'] : ['required', 'integer', 'min:1'],
            'current_slot' => ['prohibited'],
        ];
    }
    public function messages(): array
    {
        return ['name.unique' => 'Cette année académique existe déjà.', 'ends_on.after' => 'La fin doit être postérieure au début de l’année.'];
    }
}
