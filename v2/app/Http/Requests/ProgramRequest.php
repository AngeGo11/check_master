<?php
namespace App\Http\Requests;
use App\Services\AcademicAccess;
use Illuminate\Foundation\Http\FormRequest;
class ProgramRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() && app(AcademicAccess::class)->canCatalog($this->user()); }
    public function rules(): array
    {
        if ($this->routeIs('programs.store')) { return ['academic_year_id' => ['required', 'integer', 'exists:academic_years,id'], 'semester_id' => ['required', 'integer', 'exists:academic_semesters,id'], 'operation_key' => ['required', 'uuid']]; }
        $base = ['revision' => ['required', 'integer', 'min:1']];
        if ($this->routeIs('programs.publish')) { return [...$base, 'reason' => ['required', 'string', 'min:5', 'max:2000'], 'warnings_confirmed' => ['required', 'boolean']]; }
        $rules = [...$base, 'units' => ['required', 'array', 'min:1', 'max:100'], 'units.*.components' => ['present', 'array', 'max:30']];
        foreach (['units.*.', 'units.*.components.*.'] as $prefix) {
            $rules[$prefix.'code'] = ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/D']; $rules[$prefix.'name'] = ['required', 'string', 'max:150'];
            $rules[$prefix.'credits'] = ['required', 'integer', 'between:1,60']; $rules[$prefix.'hours'] = ['required', 'integer', 'between:0,999'];
        } return $rules;
    }
}
