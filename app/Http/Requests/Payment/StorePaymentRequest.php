<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contract')->property);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash,gcash,bank_transfer,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:pending,paid,overdue,failed'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
