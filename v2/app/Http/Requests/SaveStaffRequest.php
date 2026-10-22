<?php
namespace App\Http\Requests;
use App\Services\StaffAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule, Validator};
class SaveStaffRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() && app(StaffAccess::class)->canManage($this->user()); }
    protected function prepareForValidation(): void
    {
        foreach (['last_name', 'first_name', 'email', 'phone', 'position'] as $key) { if (is_string($this->input($key))) { $this->merge([$key => $key === 'email' ? Str::lower(trim($this->input($key))) : trim($this->input($key))]); } }
    }
    public function rules(): array
    {
        $update = $this->route('staff') !== null;
        $existing = $this->route('staff');
        if (! $update && is_string($this->input('operation_key'))) { $existing = \App\Models\StaffProfile::where('operation_key', $this->input('operation_key'))->first(); }
        return ['kind' => ['required', Rule::in(['teacher', 'administrative'])], 'last_name' => ['required', 'string', 'max:100'], 'first_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254', Rule::unique('staff_profiles')->ignore($existing)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()\.\-]{6,29}$/'], 'gender' => ['nullable', Rule::in(['Homme', 'Femme'])],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'hired_on' => ['nullable', 'date_format:Y-m-d'],
            'position' => ['nullable', 'string', 'max:150', Rule::prohibitedIf($this->input('kind') === 'teacher')],
            'specialty_ids' => ['present', 'array', 'max:30'], 'specialty_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'grade_id' => $update ? ['prohibited'] : ['nullable', 'integer', 'min:1'], 'grade_since' => $update ? ['prohibited'] : ['nullable', 'required_with:grade_id', 'date_format:Y-m-d', 'before_or_equal:today'],
            'function_id' => $update ? ['prohibited'] : ['nullable', 'integer', 'min:1'], 'function_since' => $update ? ['prohibited'] : ['nullable', 'required_with:function_id', 'date_format:Y-m-d', 'before_or_equal:today'],
            'operation_key' => [$update ? 'nullable' : 'required', 'uuid'], 'revision' => ['required', 'integer', $update ? 'min:1' : 'in:0'],
            'user_id' => ['prohibited'], 'password' => ['prohibited'], 'groups' => ['prohibited'], 'is_archived' => ['prohibited'], 'photo_path' => ['prohibited'], 'reference' => ['prohibited']];
    }
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) { return; }
            if ($this->input('hired_on') && $this->input('date_of_birth') && $this->input('hired_on') < $this->input('date_of_birth')) { $validator->errors()->add('hired_on', 'La date d’entrée doit suivre la naissance.'); }
            if ($this->input('kind') === 'administrative' && ($this->input('specialty_ids') || $this->input('grade_id') || $this->input('function_id'))) { $validator->errors()->add('specialty_ids', 'Les attributions académiques sont réservées aux enseignants.'); }
        }];
    }
}
