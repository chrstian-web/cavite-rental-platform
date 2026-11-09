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
            'lease_end_date' => [$this->routeIs('tenant.*') ? 'required' : 'nullable', 'date', 'after:desired_move_in_date'],
            'length_of_stay_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'number_of_occupants' => ['required', 'integer', 'min:1', 'max:20'],
            // The web form must say which one applies (it decides which IDs are needed); the API may omit it.
            'employment_status' => [$this->routeIs('tenant.*') ? 'required' : 'nullable', 'in:employed,self_employed,student,unemployed'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_number' => ['nullable', 'digits:11'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // All employment statuses require one valid ID and proof of income.
            'documents' => ['nullable', 'array'],
            'documents.valid_id_1' => ['nullable', 'required_if:employment_status,employed,self_employed,student,unemployed', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.proof_of_income' => ['nullable', 'required_if:employment_status,employed,self_employed,student,unemployed', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'employment_status.required' => 'Please choose your employment status so we know which documents to ask for.',
            'documents.valid_id_1.required_if' => 'Please upload an identification document.',
            'documents.proof_of_income.required_if' => 'Please upload your proof of income.',
            'documents.*.max' => 'Each file must be 5 MB or smaller.',
            'documents.*.mimes' => 'Files must be JPG, PNG or PDF.',
        ];
    }
}
