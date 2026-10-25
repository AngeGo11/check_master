<?php

namespace App\Http\Requests;

use App\Services\AuditTrail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditFiltersRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('audit.view') ?? false; }

    public function rules(): array
    {
        return [
            'module' => ['nullable', Rule::in(array_keys(AuditTrail::SOURCES))],
            'action' => ['nullable', 'string', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/'],
            'actor' => ['nullable', 'string', 'max:100'],
            'unattributed' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('from') ? ['after_or_equal:from'] : [])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function filters(): array
    {
        return array_filter($this->safe()->except('page'), fn ($v) => $v !== null && $v !== '' && $v !== false && $v !== '0');
    }

    public function messages(): array
    {
        return ['to.after_or_equal' => 'La date de fin doit suivre la date de début.',
            'from.date_format' => 'Indiquez une date de début valide.', 'to.date_format' => 'Indiquez une date de fin valide.'];
    }
}
