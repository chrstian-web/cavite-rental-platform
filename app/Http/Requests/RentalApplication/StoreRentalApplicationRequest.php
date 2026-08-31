<?php

namespace App\Http\Requests\RentalApplication;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenant();
    }

    public function rules(): array
    {
        return [
            'desired_move_in_date' => ['required', 'date', 'after_or_equal:today'],
            'length_of_stay_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'number_of_occupants' => ['required', 'integer', 'min:1', 'max:20'],
            'employment_status' => ['nullable', 'in:employed,self_employed,student,unemployed'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_number' => ['nullable', 'digits:11'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // One optional upload per document type; each validated independently.
            'documents' => ['nullable', 'array'],
            'documents.valid_id' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.proof_of_income' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.school_id' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.coe' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.other' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
