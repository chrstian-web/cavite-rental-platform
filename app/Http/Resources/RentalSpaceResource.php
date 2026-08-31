<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalSpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'space_number' => $this->space_number,
            'space_type' => $this->space_type,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'total_capacity' => $this->total_capacity,
            'occupied_capacity' => $this->occupied_capacity,
            'available_capacity' => max(0, $this->total_capacity - $this->occupied_capacity),
            'floor_area_sqm' => $this->floor_area_sqm,
            'is_furnished' => $this->is_furnished,
            'monthly_rent' => (float) $this->monthly_rent,
            'security_deposit' => (float) $this->security_deposit,
            'advance_payment' => (float) $this->advance_payment,
            'status' => $this->status,
            'attributes' => $this->attributes,
            'utilities_included' => $this->utilities_included,
        ];
    }
}
