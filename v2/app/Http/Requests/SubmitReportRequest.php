<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.self') && $this->user()->types()->where('user_types.id', 1)->exists();
    }
    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'document' => ['bail', 'required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:20480'],
            'operation_key' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'student_id' => ['prohibited'], 'report_id' => ['prohibited'], 'status' => ['prohibited'], 'storage_path' => ['prohibited'],
        ];
    }
    public function messages(): array
    {
        return ['title.required' => 'Indiquez le thème du mémoire.', 'document.required' => 'Sélectionnez le mémoire au format PDF.',
            'document.mimes' => 'Le fichier doit contenir un document PDF.', 'document.extensions' => 'Le fichier doit porter l’extension .pdf.',
            'document.max' => 'Le PDF ne doit pas dépasser 20 Mo.', 'document.uploaded' => 'Le transfert du PDF a échoué. Vérifiez sa taille et réessayez.'];
    }
}
