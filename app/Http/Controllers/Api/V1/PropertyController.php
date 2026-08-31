<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\FiltersAndSortsProperties;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PropertyDetailResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends ApiController
{
    use FiltersAndSortsProperties;

    public function index(Request $request): JsonResponse
    {
        $sort = $this->resolveSort($request);

        $properties = $this->applyPropertySort(
            $this->applyPropertyFilters(
                $this->baseVerifiedAvailableQuery()
                    ->with(['images' => fn ($q) => $q->where('is_cover', true), 'location'])
                    ->withCount('rentalSpaces')
                    ->withAvg('reviews', 'rating'),
                $request
            ),
            $sort
        )
            ->paginate($request->integer('per_page', 15));

        return $this->success(PropertyResource::collection($properties), 'Properties retrieved successfully.', 200, [
            'sort' => $sort,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $property = Property::query()
            ->with(['images', 'amenities', 'owner', 'location', 'barangay', 'rentalSpaces', 'virtualTour'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            ->where('verification_status', 'verified')
            ->firstOrFail();

        return $this->success(new PropertyDetailResource($property), 'Property retrieved successfully.');
    }
}
