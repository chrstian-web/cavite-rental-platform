<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class AddPaymentDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addDetails', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:100'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_number.required' => 'Please enter the reference number from your payment.',
            'proof.required' => 'Please attach a screenshot of your payment.',
            'proof.mimes' => 'The screenshot must be a JPG, PNG or PDF file.',
            'proof.max' => 'The screenshot must be 5 MB or smaller.',
        ];
    }
}
