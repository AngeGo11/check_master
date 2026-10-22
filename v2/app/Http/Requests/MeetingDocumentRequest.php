<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class MeetingDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('meetings.manage') ?? false; }
    public function rules(): array { return ['revision' => ['required', 'integer', 'min:1'], 'operation_key' => ['required', 'uuid'], 'title' => ['required', 'string', 'max:200'], 'document' => ['required', 'file', 'mimes:pdf', 'max:20480'], 'uploaded_by' => ['prohibited']]; }
}
