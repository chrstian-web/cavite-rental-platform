<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Property::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'in:condominium,boarding_house,dormitory'],
            'description' => ['nullable', 'string'],
            'location_id' => ['required', 'exists:locations,id'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'address_line' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'digits:11'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'min_monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'max_monthly_rent' => ['nullable', 'numeric', 'min:0', 'gte:min_monthly_rent'],
            'house_rules' => ['nullable', 'array'],
            'house_rules.*' => ['string', 'max:255'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['exists:amenities,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:5120'], // 5MB each
        ];
    }
}
