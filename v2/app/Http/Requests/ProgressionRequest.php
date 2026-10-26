<?php
namespace App\Http\Requests;
use App\Models\{AnnualDecision, RetakeSubject};
use App\Services\ProgressionAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProgressionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(ProgressionAccess::class);
        return $this->routeIs('progression.fees.save') ? $access->fees($this->user()) : $access->manage($this->user());
    }
    public function rules(): array
    {
        if ($this->routeIs('progression.fees.save')) { return ['academic_year_id' => ['required', 'integer', 'exists:academic_years,id'], 'study_level_id' => ['required', 'integer', 'exists:study_levels,id'], 'amount' => ['required', 'integer', 'min:0', 'max:1000000000'], 'revision' => ['required', 'integer', 'min:0']]; }
        $base = ['operation_key' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0']];
        if ($this->routeIs('progression.register')) { return [...$base, 'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'], 'enrolled_on' => ['required', 'date_format:Y-m-d'], 'quote_fingerprint' => ['required', 'string', 'size:64']]; }
        $base += ['reference' => ['required', 'string', 'max:150'], 'reason' => ['required', 'string', 'min:10', 'max:5000']];
        if ($this->routeIs('progression.retake')) { return [...$base, 'status' => ['required', Rule::in(array_keys(RetakeSubject::STATUSES))], 'score' => ['nullable', 'string', 'max:5']]; }
        return [...$base, 'outcome' => ['required', Rule::in(array_keys(AnnualDecision::OUTCOMES))], 'source_fingerprint' => ['required', 'string', 'size:64'],
            'selected' => ['sometimes', 'array', 'max:300'], 'selected.*' => ['required', 'string', 'distinct', 'max:150'], 'partial_confirmed' => ['required', 'boolean']];
    }
}
