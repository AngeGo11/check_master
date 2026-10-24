<?php

namespace App\Http\Requests;

use App\Services\ClaimAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->routeIs('claims.store') ? app(ClaimAccess::class)->personal($this->user()) : app(ClaimAccess::class)->canView($this->user(), $this->route('claim')));
    }
    public function rules(): array
    {
        $base = ['body' => ['required', 'string', 'min:5', 'max:5000'], 'operation_key' => ['required', 'uuid']];
        if ($this->routeIs('claims.store')) {
            return [...$base, 'enrollment_id' => ['required', 'integer', 'min:1'], 'subject' => ['required', 'string', 'min:5', 'max:150'],
                'grade_publication_id' => ['nullable', 'integer', 'min:1'], 'subject_keys' => ['sometimes', 'array', 'max:20'], 'subject_keys.*' => ['required', 'string', 'distinct', 'max:80'],
                'document' => ['nullable', 'file', 'mimes:pdf', 'max:10240']];
        }
        return [...$base, 'revision' => ['required', 'integer', 'min:1'], 'action' => ['required', Rule::in(['review', 'comment', 'escalate', 'resolve', 'reopen', 'withdraw'])],
            'assigned_to' => ['nullable', 'required_if:action,escalate', 'integer', 'min:1']];
    }
}
