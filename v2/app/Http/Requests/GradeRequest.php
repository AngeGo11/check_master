<?php
namespace App\Http\Requests;
use App\Services\AcademicAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class GradeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() && app(AcademicAccess::class)->canManage($this->user()); }
    public function rules(): array
    {
        if ($this->routeIs('grades.store')) { return ['enrollment_id' => ['required', 'integer', 'exists:enrollments,id'], 'semester_id' => ['required', 'integer', 'exists:academic_semesters,id']]; }
        $base = ['revision' => ['required', 'integer', 'min:1']];
        return [...$base, ...match ($this->route()->getName()) {
            'grades.update' => ['marks' => ['required', 'array', 'max:3000'], 'marks.*' => ['nullable', 'regex:/^(?:0|[1-9]|1[0-9]|20)(?:[.,][0-9]{1,2})?$/D']],
            'grades.publish' => ['decision' => ['required', Rule::in(['pending', 'validated', 'authorized', 'adjourned'])], 'reason' => ['required', 'string', 'min:5', 'max:2000']],
            default => ['reason' => ['required', 'string', 'min:5', 'max:2000']],
        }];
    }
}
