<?php

namespace App\Http\Controllers\Public;

use App\Http\Concerns\FiltersAndSortsProperties;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\SearchLog;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
                    ->withBoostFlag()
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
     * Live result count for the landing-page search. Same filters as index(),
     * but it is not logged as a search: it fires while the visitor is still choosing.
     */
    public function count(Request $request): JsonResponse
    {
        $count = $this->applyPropertyFilters($this->baseVerifiedAvailableQuery(), $request)->count();

        return response()->json(['count' => $count]);
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
                'rentalSpaces' => fn ($q) => $q->orderBy('space_number')
                    ->withCount(['rentalContracts as active_contracts_count' => fn ($c) => $c->where('status', 'active')]),
                'rentalSpaces.images',
                'virtualTour.scenes.hotspots',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            ->firstOrFail();

        // Over the owner's plan limit: hidden from tenants, still visible to the owner and admins.
        abort_if(
            $property->subscription_hidden && ! (auth()->check() && auth()->user()->can('update', $property)),
            404
        );

        $property->increment('views_count');

        return view('properties.show', compact('property'));
    }

    /**
     * Serve a tour panorama without exposing the public storage path.
     * Published scenes are available to listing visitors; draft scenes are
     * available only to users who can update the property (owner preview).
     */
    public function panorama(Property $property, VirtualTour $tour, VirtualTourScene $scene): BinaryFileResponse
    {
        abort_unless(
            $tour->property_id === $property->id
            && $scene->virtual_tour_id === $tour->id,
            404
        );

        if ($tour->status !== 'published') {
            abort_unless(
                auth()->check() && auth()->user()->can('update', $property),
                404
            );
        }

        $disk = Storage::disk('public');
        abort_unless($disk->exists($scene->panorama_image), 404);

        return response()->file($disk->path($scene->panorama_image), [
            'Cache-Control' => $tour->status === 'published'
                ? 'public, max-age=3600'
                : 'private, no-store',
        ]);
    }
}
