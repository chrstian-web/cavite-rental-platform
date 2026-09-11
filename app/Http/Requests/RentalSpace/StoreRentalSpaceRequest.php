<?php

namespace App\Http\Requests\RentalSpace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A rental space always belongs to a property; authorize against that property.
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        return [
            'space_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rental_spaces')->where(function ($query) {
                    return $query->where('property_id', $this->route('property')->id);
                }),
            ],
            'space_type' => ['nullable', 'string', 'max:100'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'total_capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'is_furnished' => ['boolean'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'security_deposit' => ['nullable', 'numeric', 'min:0'],
            'advance_payment' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,reserved,maintenance,inactive'],
            'utilities_included' => ['nullable', 'array'],
            'utilities_included.*' => ['string', 'max:50'],

            // Type-specific fields, stored into the `attributes` JSON column.
            // Condominium:
            'attributes.floor' => ['nullable', 'string', 'max:20'],
            'attributes.unit_type' => ['nullable', 'string', 'max:50'],
            // Boarding house:
            'attributes.room_type' => ['nullable', 'string', 'max:50'],
            // Dormitory:
            'attributes.gender_restriction' => ['nullable', 'in:male,female,mixed'],
            'attributes.curfew_time' => ['nullable', 'date_format:H:i'],

            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_furnished' => $this->boolean('is_furnished'),
        ]);
    }
}
