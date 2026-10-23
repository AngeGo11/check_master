<?php
namespace App\Http\Requests;
use App\Services\DefenseAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class DefenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) { return false; } $a = app(DefenseAccess::class); $u = $this->user();
        return match ($this->route()->getName()) { 'defenses.store' => $a->own($u), 'defenses.schedule' => $a->canManage($u), 'defenses.result' => $a->canAssess($u, $this->route('defense')), default => $a->canManage($u) || $a->own($u) };
    }
    public function rules(): array
    {
        $blocked = ['student_id' => ['prohibited'], 'status' => ['prohibited'], 'result_by' => ['prohibited'], 'report_version_id' => ['prohibited'], 'report_round_id' => ['prohibited']];
        if ($this->routeIs('defenses.store')) { return [...$blocked, 'report_id' => ['required', 'integer', 'min:1'], 'report_revision' => ['required', 'integer', 'min:1'], 'operation_key' => ['required', 'uuid']]; }
        $base = [...$blocked, 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:5', 'max:2000']];
        return [...$base, ...match ($this->route()->getName()) {
            'defenses.schedule' => ['starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'duration_minutes' => ['required', 'integer', 'between:15,240'], 'location' => ['required', 'string', 'max:150'], 'instructions' => ['nullable', 'string', 'max:4000'], 'jurors' => ['required', 'array', 'min:2', 'max:8'], 'jurors.*.user_id' => ['required', 'integer', 'min:1', 'distinct'], 'jurors.*.role' => ['required', Rule::in(['president', 'examiner', 'supervisor'])]],
            'defenses.result' => ['outcome' => ['required', Rule::in(['validated', 'adjourned', 'absent'])], 'score' => ['nullable', 'regex:/^(?:0|[1-9]|1[0-9]|20)(?:[.,][0-9]{1,2})?$/D']],
            default => [],
        }];
    }
}
