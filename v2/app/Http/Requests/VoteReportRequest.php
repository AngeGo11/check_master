<?php
namespace App\Http\Requests;

use App\Services\ReportAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoteReportRequest extends FormRequest
{
    public function authorize(): bool { return app(ReportAccess::class)->canVote($this->user()); }
    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approved', 'changes_requested'])],
            'comment' => ['nullable', 'required_if:decision,changes_requested', 'string', 'min:5', 'max:3000'],
            'user_id' => ['prohibited'], 'reviewed_by' => ['prohibited'], 'member_id' => ['prohibited'], 'status' => ['prohibited'], 'report_version_id' => ['prohibited']];
    }
    public function messages(): array { return ['comment.required_if' => 'Expliquez la correction demandée à l’étudiant.']; }
}
