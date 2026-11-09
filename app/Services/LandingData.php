<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Property;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Everything the public landing page needs, computed once and cached.
 *
 * Only verified + available properties are counted, the same rule the public
 * /properties search uses, so the numbers on the home page always match what
 * a visitor finds after clicking through.
 */
class LandingData
{
    private const CACHE_KEY = 'landing.v1';
    private const TTL_SECONDS = 600;

    /**
     * Approximate city/town centers, used only when a Location row has no
     * latitude/longitude of its own. Keys are lower-case ASCII names.
     * Fill in the latitude/longitude columns in the locations table to override.
     */
    private const FALLBACK_COORDS = [
        'dasmarinas' => [14.3294, 120.9367],
        'general trias' => [14.3869, 120.8814],
        'imus' => [14.4297, 120.9367],
        'bacoor' => [14.4624, 120.9645],
        'kawit' => [14.4457, 120.9019],
        'tanza' => [14.3394, 120.8508],
        'trece martires' => [14.2820, 120.8670],
        'silang' => [14.2306, 120.9747],
        'carmona' => [14.3136, 121.0576],
        'tagaytay' => [14.1153, 120.9621],
        'naic' => [14.3167, 120.7667],
        'rosario' => [14.4156, 120.8586],
        'general mariano alvarez' => [14.2986, 121.0053],
        'magallanes' => [14.1744, 120.7500],
        'maragondon' => [14.2733, 120.7350],
        'amadeo' => [14.1698, 120.9201],
        'mendez' => [14.1305, 120.9034],
        'alfonso' => [14.1370, 120.8570],
        'indang' => [14.1953, 120.8774],
        'gen. emilio aguinaldo' => [14.1500, 120.7800],
    ];

    private const TYPE_LABELS = [
        'condominium' => 'Condominium',
        'boarding_house' => 'Boarding house',
        'dormitory' => 'Dormitory',
    ];

    public static function get(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn () => self::build());
        } catch (Throwable) {
            // A cache or database hiccup must never take the home page down.
            return self::empty();
        }
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function empty(): array
    {
        return ['total' => 0, 'tours' => 0, 'lowest' => null, 'cities' => [], 'featured' => []];
    }

    private static function build(): array
    {
        if (! Schema::hasTable('properties') || ! Schema::hasTable('locations')) {
            return self::empty();
        }

        $base = fn () => Property::query()->available()->where('verification_status', 'verified');

        $total = $base()->count();
        if ($total === 0) {
            return self::empty();
        }

        $tours = $base()
            ->whereHas('virtualTour', fn ($q) => $q->where('status', 'published'))
            ->count();

        $lowest = $base()->whereNotNull('min_monthly_rent')->min('min_monthly_rent');

        $perCity = $base()
            ->selectRaw('location_id, COUNT(*) as listings, MIN(min_monthly_rent) as lowest')
            ->groupBy('location_id')
            ->get()
            ->keyBy('location_id');

        $cities = Location::query()
            ->where('is_active', true)
            ->whereIn('id', $perCity->keys())
            ->get()
            ->map(function (Location $loc) use ($perCity) {
                $row = $perCity[$loc->id];
                $key = Str::lower(Str::ascii($loc->city_municipality));

                $lat = $loc->latitude !== null ? (float) $loc->latitude : (self::FALLBACK_COORDS[$key][0] ?? null);
                $lng = $loc->longitude !== null ? (float) $loc->longitude : (self::FALLBACK_COORDS[$key][1] ?? null);
                $min = $row->lowest !== null ? (float) $row->lowest : null;

                return [
                    'id' => $loc->id,
                    'name' => $loc->city_municipality,
                    'listings' => (int) $row->listings,
                    'lowest' => $min,
                    'label' => $min !== null ? self::shortPeso($min) : null,
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            })
            ->sortByDesc('listings')
            ->values()
            ->all();

        $featured = $base()
            ->with([
                'images' => fn ($q) => $q->where('is_cover', true),
                'location',
                'virtualTour',
            ])
            ->orderByDesc('is_featured')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Property $p) => [
                'name' => $p->name,
                'slug' => $p->slug,
                'city' => $p->location?->city_municipality,
                'type' => self::TYPE_LABELS[$p->property_type] ?? str($p->property_type)->replace('_', ' ')->title()->toString(),
                'price' => self::priceRange($p->min_monthly_rent, $p->max_monthly_rent),
                // Relative URL so a cached value still works if the host name changes.
                'image' => ($path = $p->images->first()?->path) ? '/storage/'.ltrim($path, '/') : null,
                'tour' => $p->virtualTour?->status === 'published',
            ])
            ->all();

        return [
            'total' => $total,
            'tours' => $tours,
            'lowest' => $lowest !== null ? (float) $lowest : null,
            'cities' => $cities,
            'featured' => $featured,
        ];
    }

    /** 3500 -> "3.5k", 12000 -> "12k", 850 -> "850" */
    private static function shortPeso(float $amount): string
    {
        if ($amount < 1000) {
            return '₱'.number_format($amount);
        }

        $k = round($amount / 1000, 1);

        return '₱'.rtrim(rtrim(number_format($k, 1), '0'), '.').'k';
    }

    private static function priceRange($min, $max): string
    {
        if (! $min) {
            return 'Price on request';
        }

        $out = '₱'.number_format((float) $min);
        if ($max && (float) $max !== (float) $min) {
            $out .= ' – ₱'.number_format((float) $max);
        }

        return $out.' / month';
    }
}
