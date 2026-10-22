<?php
namespace App\Http\Requests;
use App\Models\InternalMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SaveMessageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('messages.use') ?? false; }
    public function rules(): array
    {
        return ['action' => ['required', Rule::in(['draft', 'send'])], 'operation_key' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'subject' => [Rule::requiredIf($this->input('action') === 'send'), 'nullable', 'string', 'max:255'],
            'body' => [Rule::requiredIf($this->input('action') === 'send'), 'nullable', 'string', 'max:20000'],
            'priority' => ['required', Rule::in(InternalMessage::PRIORITIES)], 'category' => ['required', Rule::in(InternalMessage::CATEGORIES)],
            'recipient_ids' => ['present', 'array', 'max:200'], 'recipient_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'source_id' => ['nullable', 'integer', 'min:1', 'required_with:source_mode'], 'source_mode' => ['nullable', Rule::in(['reply', 'forward']), 'required_with:source_id'],
            'sender_id' => ['prohibited'], 'status' => ['prohibited'], 'kind' => ['prohibited'], 'meeting_id' => ['prohibited'], 'read_at' => ['prohibited'], 'group_ids' => ['prohibited']];
    }
}
