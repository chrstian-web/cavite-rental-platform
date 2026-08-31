<?php

namespace App\Http\Concerns;

use App\Models\Location;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared by the public web search and the API property listing, so the two
 * front ends can never silently drift into different filter/sort behavior.
 */
trait FiltersAndSortsProperties
{
    protected function baseVerifiedAvailableQuery(): Builder
    {
        return Property::query()
            ->available()
            ->where('verification_status', 'verified');
    }

    protected function applyPropertyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('type'), fn ($q) => $q->ofType($request->string('type')))
            ->when($request->filled('location_id'), fn ($q) => $q->inLocation((int) $request->integer('location_id')))
            ->when(
                $request->filled('min_rent') || $request->filled('max_rent'),
                fn ($q) => $q->withinBudget($request->float('min_rent') ?: null, $request->float('max_rent') ?: null)
            );
    }

    /**
     * "Recommended" is deliberately not offered as a generic sort — a real
     * recommendation needs the renter's actual preferences, which is exactly
     * what the dedicated DSS flow (/tenant/recommendations, POST /api/v1/recommendations)
     * collects. A fake "Recommended" sort with no preferences would just be
     * newest-first wearing a misleading label.
     */
    protected function resolveSort(Request $request): string
    {
        $allowed = ['price_low', 'price_high', 'newest', 'most_viewed', 'highest_rated'];
        $sort = $request->string('sort')->toString();

        return in_array($sort, $allowed, true) ? $sort : 'newest';
    }

    protected function applyPropertySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_low' => $query->orderByRaw('COALESCE(min_monthly_rent, 999999999) asc'),
            'price_high' => $query->orderByRaw('COALESCE(max_monthly_rent, 0) desc'),
            'most_viewed' => $query->orderByDesc('views_count'),
            'highest_rated' => $query->orderByRaw('reviews_avg_rating IS NULL, reviews_avg_rating desc'),
            default => $query->latest(),
        };
    }

    protected function activeLocations()
    {
        return Location::where('is_active', true)->orderBy('city_municipality')->get();
    }
}
