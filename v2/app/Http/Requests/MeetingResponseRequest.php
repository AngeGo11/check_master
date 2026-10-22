<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class MeetingResponseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('meetings.view') ?? false; }
    public function rules(): array { return ['revision' => ['required', 'integer', 'min:1'], 'response_revision' => ['required', 'integer', 'min:1'], 'response' => ['required', Rule::in(['accepted', 'declined'])], 'user_id' => ['prohibited']]; }
}
