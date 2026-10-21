<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('payments.manage') ?? false; }
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'method' => ['required', Rule::in(['cash', 'cheque'])],
            'cheque_number' => ['nullable', 'required_if:method,cheque', 'prohibited_if:method,cash', 'string', 'max:50'],
            'purpose' => ['required', 'string', 'max:255'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'operation_key' => ['required', 'uuid'],
            'revision' => ['required', 'integer', 'min:1'],
            'amount_due' => ['prohibited'], 'paid_amount' => ['prohibited'], 'receipt_number' => ['prohibited'],
        ];
    }
    public function messages(): array
    {
        return ['amount.integer' => 'Saisissez un montant entier en FCFA.', 'amount.min' => 'Le montant payé doit être strictement positif.', 'cheque_number.required_if' => 'Indiquez le numéro du chèque.', 'paid_on.before_or_equal' => 'La date du paiement ne peut pas être future.'];
    }
}
