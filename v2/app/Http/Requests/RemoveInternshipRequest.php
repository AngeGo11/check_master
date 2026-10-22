<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class RemoveInternshipRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('internships.self') && $this->user()->belongsToCompatibleGroup('student'); }
    public function rules(): array { return ['reason' => ['required', 'string', 'min:5', 'max:1000'], 'revision' => ['required', 'integer', 'min:1']]; }
}
