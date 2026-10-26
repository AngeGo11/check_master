<?php
namespace App\Http\Requests;
use App\Services\DocumentAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $a = app(DocumentAccess::class); $u = $this->user();
        if ($this->routeIs('documents.archive')) { return $a->archive($u); }
        if ($this->routeIs('documents.deposit')) { return $a->personal($u); }
        if ($this->routeIs('documents.publish')) { return $a->writer($u); }
        return $a->personal($u) || $a->writer($u);
    }
    public function rules(): array
    {
        $base = ['operation_key' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0']];
        if ($this->routeIs('documents.archive')) { return [...$base, 'source_type' => ['required', Rule::in(['report', 'document', 'extract'])], 'source_id' => ['required', 'integer', 'min:1'], 'active' => ['required', 'boolean']]; }
        if ($this->routeIs('documents.deposit')) { return [...$base, 'enrollment_id' => ['required', 'integer', 'min:1']]; }
        $base['reason'] = ['required', 'string', 'min:10', 'max:1000'];
        if ($this->routeIs('documents.publish')) { return [...$base, 'publication_reference' => ['required', 'string', 'max:150']]; }
        return [...$base, 'kind' => ['required', Rule::in(['personal', 'minutes'])], 'format' => ['required', Rule::in(['text', 'pdf'])], 'title' => ['required', 'string', 'max:200'],
            'document' => ['nullable', 'file', 'max:20480'], 'sections' => ['sometimes', 'array', 'max:20'],
            'sections.*.heading' => ['nullable', 'string', 'max:150'], 'sections.*.body' => ['required', 'string', 'max:5000'], 'sections.*.style' => ['required', Rule::in(['paragraph', 'list', 'quote'])],
            'entries' => ['sometimes', 'array', 'max:10'], 'entries.*.round_id' => ['required', 'integer', 'min:1', 'distinct'],
            'entries.*.source_fingerprint' => ['required', 'string', 'size:64'], 'entries.*.body' => ['required', 'string', 'min:10', 'max:3000']];
    }
}
