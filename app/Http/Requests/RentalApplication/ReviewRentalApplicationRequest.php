<?php

namespace App\Http\Requests\RentalApplication;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRentalApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('application'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:under_review,approved,rejected'],
            'decision_reason' => ['nullable', 'string', 'max:1000'],
            'reservation_fee' => ['required_if:status,approved', 'nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'reservation_terms' => ['required_if:status,approved', 'nullable', 'string', 'max:10000'],
        ];
    }
}
