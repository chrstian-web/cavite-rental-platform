<?php

namespace App\Http\Requests\Dss;

use Illuminate\Foundation\Http\FormRequest;

class RecommendationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenant();
    }

    public function rules(): array
    {
        return [
            'max_budget' => ['nullable', 'numeric', 'min:0'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'property_type' => ['nullable', 'in:condominium,boarding_house,dormitory'],
            'number_of_occupants' => ['nullable', 'integer', 'min:1', 'max:20'],
            'required_bedrooms' => ['nullable', 'integer', 'min:0', 'max:10'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['exists:amenities,id'],
            'furnishing' => ['nullable', 'in:furnished,unfurnished'],
            'max_distance_km' => ['nullable', 'numeric', 'min:0'],
            'reference_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'reference_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
