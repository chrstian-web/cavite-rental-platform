<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ReviewPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject,request_correction'],
            'reason' => ['required_if:action,reject,request_correction', 'nullable', 'string', 'max:1000'],
        ];
    }
}
