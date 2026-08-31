<?php

namespace App\Http\Controllers\Public;

use App\Http\Concerns\FiltersAndSortsProperties;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    use FiltersAndSortsProperties;

    public function index(Request $request): View
    {
        $this->logSearch($request);

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
            ->paginate(12)
            ->withQueryString();

        $locations = $this->activeLocations();

        return view('properties.index', compact('properties', 'locations', 'sort'));
    }

    /**
     * Only log a search when it carries at least one real filter — a plain
     * unfiltered visit to /properties isn't a "search" worth reporting on.
     */
    protected function logSearch(Request $request): void
    {
        $hasFilter = $request->filled('location_id') || $request->filled('type')
            || $request->filled('min_rent') || $request->filled('max_rent');

        if (! $hasFilter) {
            return;
        }

        SearchLog::create([
            'location_id' => $request->integer('location_id') ?: null,
            'property_type' => $request->string('type')->toString() ?: null,
            'min_rent' => $request->float('min_rent') ?: null,
            'max_rent' => $request->float('max_rent') ?: null,
            'ip_address' => $request->ip(),
        ]);
    }

    public function show(string $slug): View
    {
        $property = Property::query()
            ->with([
                'images', 'amenities', 'owner', 'location', 'barangay',
                'rentalSpaces' => fn ($q) => $q->orderBy('space_number'),
                'rentalSpaces.images',
                'virtualTour.scenes.hotspots',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            ->firstOrFail();

        $property->increment('views_count');

        return view('properties.show', compact('property'));
    }
}
