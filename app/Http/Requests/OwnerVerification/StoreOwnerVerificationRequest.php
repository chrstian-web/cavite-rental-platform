<?php

namespace App\Http\Requests\OwnerVerification;

use App\Services\OwnerVerificationService;
use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner();
    }

    public function rules(): array
    {
        $rules = [];

        foreach (OwnerVerificationService::DOCUMENT_TYPES as $type => $config) {
            $rules["documents.{$type}"] = [
                $config['required'] ? 'required' : 'nullable',
                'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120', // 5MB
            ];
            $rules["expiration_dates.{$type}"] = ['nullable', 'date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'documents.government_id.required' => 'A valid government ID is required.',
            'documents.proof_of_ownership.required' => 'Proof of property ownership or authorization is required.',
            'documents.*.mimes' => 'Please upload a clear PDF, JPG, or PNG file.',
            'documents.*.max' => 'Each file must be under 5MB.',
        ];
    }
}
