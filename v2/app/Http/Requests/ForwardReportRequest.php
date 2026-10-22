<?php
namespace App\Http\Requests;

use App\Services\ReportAccess;
use Illuminate\Foundation\Http\FormRequest;

class ForwardReportRequest extends FormRequest
{
    public function authorize(): bool { return app(ReportAccess::class)->canForward($this->user()); }
    public function rules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1'], 'committee_fingerprint' => ['required', 'string', 'size:64'],
            'reason' => ['nullable', 'string', 'min:5', 'max:1000'], 'member_ids' => ['prohibited'], 'status' => ['prohibited'], 'report_version_id' => ['prohibited']];
    }
}
