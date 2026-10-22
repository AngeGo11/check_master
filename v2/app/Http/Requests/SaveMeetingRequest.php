<?php
namespace App\Http\Requests;
use App\Services\CollaborationAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SaveMeetingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() && app(CollaborationAccess::class)->canOrganize($this->user()); }
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string', 'max:10000'], 'location' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['normal', 'urgent'])], 'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:720'], 'participant_ids' => ['required', 'array', 'min:1', 'max:200'],
            'participant_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'round_ids' => ['present', 'array', 'max:50'], 'round_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'operation_key' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'reason' => [Rule::requiredIf($this->route('meeting') !== null), 'nullable', 'string', 'min:5', 'max:1000'],
            'created_by' => ['prohibited'], 'status' => ['prohibited'], 'cancelled_at' => ['prohibited'], 'responses' => ['prohibited']];
    }
}
