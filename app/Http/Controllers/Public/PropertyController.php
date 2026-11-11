<?php

namespace App\Http\Controllers\Public;

use App\Http\Concerns\FiltersAndSortsProperties;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\SearchLog;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PropertyController extends Controller
{
    use FiltersAndSortsProperties;

    public function index(Request $request): View
    {
        $this->logSearch($request);

        $nearbySearch = null;
        if ($request->boolean('nearby')) {
            $manualLocation = trim($request->string('location')->toString());
            $nearbySearch = $manualLocation !== ''
                ? ['location' => $manualLocation]
                : $request->session()->get('nearby_search');

            if (is_array($nearbySearch) && isset($nearbySearch['location'])) {
                $request->session()->put('nearby_search', $nearbySearch);
            }
        }
        if (! is_array($nearbySearch) || ! isset($nearbySearch['location'])) {
            $nearbySearch = null;
        }

        $sort = $this->resolveSort($request);
        $query = $this->applyPropertyFilters(
            $this->baseVerifiedAvailableQuery()
                ->withBoostFlag()
                ->with(['images' => fn ($q) => $q->where('is_cover', true), 'location'])
                ->withCount('rentalSpaces')
                ->withAvg('reviews', 'rating'),
            $request
        );
        if ($nearbySearch) {
            $query = $this->applyNearbyFilter($query, $nearbySearch['location']);
        }
        $query = $this->applyPropertySort($query, $sort);
        $properties = $query
            ->paginate(12)
            ->withQueryString();

        $locations = $this->activeLocations();

        return view('properties.index', compact('properties', 'locations', 'sort', 'nearbySearch'));
    }

    public function startNearby(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $request->session()->put('nearby_search', [
            'location' => trim($data['location']),
        ]);

        return redirect()->route('properties.index', ['nearby' => 1]);
    }

    public function clearNearby(Request $request): RedirectResponse
    {
        $request->session()->forget('nearby_search');

        return redirect()->route('properties.index');
    }

    /** Match a manually entered area to listing city, barangay, or street address. */
    private function applyNearbyFilter(Builder $query, string $location): Builder
    {
        return $query
            ->where(function ($matches) use ($location) {
                $terms = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $location) ?: [])));
                foreach ($terms as $index => $term) {
                    $pattern = '%'.$term.'%';
                    $matchTerm = function ($clause) use ($pattern): void {
                        $clause->where('properties.address_line', 'like', $pattern)
                            ->orWhereHas('location', fn ($city) => $city->where('city_municipality', 'like', $pattern))
                            ->orWhereHas('barangay', fn ($barangay) => $barangay->where('name', 'like', $pattern));
                    };
                    $index === 0 ? $matchTerm($matches) : $matches->orWhere($matchTerm);
                }
            });
    }

    /**
     * Only log a search when it carries at least one real filter — a plain
     * unfiltered visit to /properties isn't a "search" worth reporting on.
     */
    protected function logSearch(Request $request): void
    {
        // Keep manually entered nearby locations out of search/IP analytics.
        if ($request->boolean('nearby')) {
            return;
        }

        $hasFilter = $request->filled('q') || $request->filled('location_id') || $request->filled('type')
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
