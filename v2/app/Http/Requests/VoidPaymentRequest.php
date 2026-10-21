<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('payments.manage') ?? false; }
    public function rules(): array { return ['reason' => ['required', 'string', 'min:5', 'max:1000'], 'revision' => ['required', 'integer', 'min:1']]; }
    public function messages(): array { return ['reason.required' => 'Expliquez la correction à effectuer.', 'reason.min' => 'Précisez le motif de l’annulation.']; }
}
