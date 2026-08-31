<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => [
                'id' => $this->property->id,
                'name' => $this->property->name,
                'slug' => $this->property->slug,
            ],
            'rental_space' => [
                'id' => $this->rentalSpace->id,
                'space_number' => $this->rentalSpace->space_number,
            ],
            'desired_move_in_date' => $this->desired_move_in_date->toDateString(),
            'number_of_occupants' => $this->number_of_occupants,
            'status' => $this->status,
            'decision_reason' => $this->decision_reason,
            'submitted_at' => $this->created_at->toIso8601String(),
        ];
    }
}
