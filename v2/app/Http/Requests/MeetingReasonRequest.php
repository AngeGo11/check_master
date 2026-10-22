<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class MeetingReasonRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('meetings.manage') ?? false; }
    public function rules(): array { return ['revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:5', 'max:1000'], 'user_id' => ['prohibited']]; }
}
