<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class MessageStateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('messages.use') ?? false; }
    public function rules(): array { return ['action' => ['required', Rule::in(['read', 'unread', 'archive', 'trash', 'restore'])], 'revision' => ['required', 'integer', 'min:1'], 'user_id' => ['prohibited'], 'recipient_id' => ['prohibited']]; }
}
