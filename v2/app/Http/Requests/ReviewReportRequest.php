<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewReportRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('reports.review') ?? false; }
    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approved', 'changes_requested'])], 'comment' => ['required', 'string', 'min:5', 'max:3000'],
            'revision' => ['required', 'integer', 'min:1'], 'report_version_id' => ['prohibited'], 'status' => ['prohibited']];
    }
    public function messages(): array { return ['comment.required' => 'Ajoutez un commentaire pour l’étudiant.', 'comment.min' => 'Le commentaire doit contenir au moins 5 caractères.']; }
}
