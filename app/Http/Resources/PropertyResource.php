<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for listing endpoints — deliberately excludes the
 * full description, amenities, and rental spaces to keep list payloads small
 * for mobile clients. Use PropertyDetailResource for a single property.
 */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->images->firstWhere('is_cover', true) ?? $this->images->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'property_type' => $this->property_type,
            'city_municipality' => $this->whenLoaded('location', fn () => $this->location->city_municipality),
            'min_monthly_rent' => $this->min_monthly_rent ? (float) $this->min_monthly_rent : null,
            'max_monthly_rent' => $this->max_monthly_rent ? (float) $this->max_monthly_rent : null,
            'cover_image' => $cover ? asset('storage/'.$cover->path) : null,
            'is_featured' => $this->is_featured,
            'units_count' => $this->whenCounted('rentalSpaces'),
        ];
    }
}
