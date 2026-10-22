<?php
namespace App\Http\Requests;
use App\Services\StaffAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StaffCatalogRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() && app(StaffAccess::class)->canCatalog($this->user()); }
    public function rules(): array { return ['kind' => ['required', Rule::in(['grade', 'function', 'specialty'])], 'name' => ['required', 'string', 'max:150'], 'is_active' => ['required', 'boolean'], 'revision' => ['required', 'integer', $this->route('reference') ? 'min:1' : 'in:0'], 'legacy_id' => ['prohibited']]; }
}
