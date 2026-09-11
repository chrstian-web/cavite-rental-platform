<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submit', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,gcash,bank_transfer,other'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['required', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * The record's due amount is the source of truth. The tenant's `amount`
     * field is display-only in the UI, but we still validate it server-side
     * against tampering, and reject anything that doesn't match exactly —
     * this schema has no concept of a partial payment, so a mismatch (over
     * or under) is always a mistake, not a valid submission.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $payment = $this->route('payment');
            $submitted = (float) $this->input('amount');
            $due = (float) $payment->amount;

            if (abs($submitted - $due) > 0.001) {
                $validator->errors()->add(
                    'amount',
                    "The amount must match the due balance of ₱".number_format($due, 2).'.'
                );
            }
        });
    }
}
