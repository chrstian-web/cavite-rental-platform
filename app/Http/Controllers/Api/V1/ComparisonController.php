<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PropertyDetailResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComparisonController extends ApiController
{
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_ids' => ['required', 'array', 'min:2', 'max:4'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ]);

        $properties = Property::query()
            ->with(['images', 'amenities', 'location', 'rentalSpaces'])
            ->withAvg('reviews', 'rating')
            ->whereIn('id', $validated['property_ids'])
            ->get();

        return $this->success(PropertyDetailResource::collection($properties), 'Comparison retrieved successfully.');
    }
}
