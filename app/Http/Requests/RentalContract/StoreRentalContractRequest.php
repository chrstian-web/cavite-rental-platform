<?php

namespace App\Http\Requests\RentalContract;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('application')->property);
    }

    public function rules(): array
    {
        return [
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'security_deposit' => ['nullable', 'numeric', 'min:0'],
            'advance_payment' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'terms_and_conditions' => ['nullable', 'string'],
        ];
    }
}
