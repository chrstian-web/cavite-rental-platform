<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PropertyResource;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with(['property.images' => fn ($q) => $q->where('is_cover', true), 'property.location'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        $favorites->setCollection($favorites->getCollection()->map->property);

        return $this->success(PropertyResource::collection($favorites), 'Favorites retrieved successfully.');
    }

    public function store(Request $request, Property $property): JsonResponse
    {
        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'property_id' => $property->id,
        ]);

        return $this->success(null, $favorite->wasRecentlyCreated ? 'Added to favorites.' : 'Already in favorites.');
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        Favorite::where('user_id', $request->user()->id)->where('property_id', $property->id)->delete();

        return $this->success(null, 'Removed from favorites.');
    }
}
