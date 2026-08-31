<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'property_type' => $this->property_type,
            'description' => $this->description,
            'address_line' => $this->address_line,
            'city_municipality' => $this->location->city_municipality,
            'barangay' => $this->barangay?->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'contact_person' => $this->contact_person,
            'contact_number' => $this->contact_number,
            'contact_email' => $this->contact_email,
            'min_monthly_rent' => $this->min_monthly_rent ? (float) $this->min_monthly_rent : null,
            'max_monthly_rent' => $this->max_monthly_rent ? (float) $this->max_monthly_rent : null,
            'house_rules' => $this->house_rules ?? [],
            'availability_status' => $this->availability_status,
            'views_count' => $this->views_count,
            'images' => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => asset('storage/'.$img->path),
                'is_cover' => $img->is_cover,
            ]),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'rental_spaces' => RentalSpaceResource::collection($this->whenLoaded('rentalSpaces')),
            'has_virtual_tour' => $this->virtualTour && $this->virtualTour->status === 'published',
            'average_rating' => $this->reviews_avg_rating ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => $this->reviews_count ?? 0,
            'owner' => [
                'name' => $this->owner->first_name.' '.$this->owner->last_name,
            ],
        ];
    }
}
