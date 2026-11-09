<?php

namespace App\Http\Controllers\Owner;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PropertyMapSearchController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $query = preg_replace('/\s+/u', ' ', trim($validated['q']));
        $cacheKey = 'maps:nominatim:'.hash('sha256', mb_strtolower($query));

        if (Cache::has($cacheKey)) {
            return response()->json(['results' => Cache::get($cacheKey)]);
        }

        // Keep uncached requests below Nominatim's public API limit across app users.
        if (! Cache::add('maps:nominatim:request-window', true, now()->addSeconds(2))) {
            return response()->json([
                'message' => 'Address search is busy. Please wait a moment and try again.',
            ], 429);
        }

        try {
            $response = Http::acceptJson()
                ->withUserAgent('CaviteRentals/1.0 (+'.config('app.url').')')
                ->connectTimeout(4)
                ->timeout(8)
                ->get(config('services.maps.geocoder_url'), [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 5,
                    'countrycodes' => 'ph',
                    'addressdetails' => 0,
                ]);
        } catch (ConnectionException) {
            return response()->json([
                'message' => 'The map search service could not be reached. You can still place the pin manually on the map.',
            ], 503);
        }

        if (! $response->successful()) {
            return response()->json([
                'message' => 'The map search service is temporarily unavailable. Please place the pin manually on the map.',
            ], 503);
        }

        $results = collect($response->json())
            ->take(5)
            ->filter(fn ($item) => is_array($item)
                && is_numeric($item['lat'] ?? null)
                && is_numeric($item['lon'] ?? null)
                && isset($item['display_name']))
            ->map(fn (array $item) => [
                'label' => (string) $item['display_name'],
                'latitude' => (float) $item['lat'],
                'longitude' => (float) $item['lon'],
            ])
            ->values()
            ->all();

        Cache::put($cacheKey, $results, now()->addDays(7));

        return response()->json(['results' => $results]);
    }
}
