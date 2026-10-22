<?php
namespace App\Http\Requests;
use App\Services\StaffAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StaffChangeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() && app(StaffAccess::class)->canManage($this->user()) && (! $this->routeIs('staff.account') || $this->user()->can('users.manage')); }
    public function rules(): array
    {
        $rules = ['revision' => ['required', 'integer', 'min:1'], 'user_id' => ['prohibited'], 'password' => ['prohibited'], 'groups' => ['prohibited'], 'photo_path' => ['prohibited']];
        return [...$rules, ...match ($this->route()->getName()) {
            'staff.archive' => ['is_archived' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:1000']],
            'staff.account' => ['account_email' => ['present', 'nullable', 'email', 'max:254']],
            'staff.assignment' => ['kind' => ['required', Rule::in(['grade', 'function'])], 'reference_id' => ['present', 'nullable', 'integer', 'min:1'], 'effective_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']],
            'staff.photo.store' => ['photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'], 'operation_key' => ['required', 'uuid']],
            default => [],
        }];
    }
}
