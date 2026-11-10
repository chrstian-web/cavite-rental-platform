{{--
    Location map for a property. Expects $property (with location + barangay loaded).
    Prefers the property's own pin, then the city centre, then falls back to a
    text search on the address (same rules as the public property page).
--}}
@php
    $propertyLat = $property->latitude;
    $propertyLng = $property->longitude;
    $hasPropertyCoordinates = is_numeric($propertyLat) && is_numeric($propertyLng)
        && abs((float) $propertyLat) <= 90
        && abs((float) $propertyLng) <= 180
        && ! ((float) $propertyLat === 0.0 && (float) $propertyLng === 0.0);
    $locationLat = $property->location?->latitude;
    $locationLng = $property->location?->longitude;
    $hasLocationCoordinates = ! $hasPropertyCoordinates
        && is_numeric($locationLat) && is_numeric($locationLng)
        && abs((float) $locationLat) <= 90
        && abs((float) $locationLng) <= 180
        && ! ((float) $locationLat === 0.0 && (float) $locationLng === 0.0);
    $mapQuery = $hasPropertyCoordinates
        ? $propertyLat.','.$propertyLng
        : ($hasLocationCoordinates
            ? $locationLat.','.$locationLng
            : trim(implode(', ', array_filter([
                $property->address_line,
                $property->barangay?->name,
                $property->location?->city_municipality,
                $property->location?->province,
            ]))));
    $mapZoom = $hasPropertyCoordinates ? 16 : ($hasLocationCoordinates ? 13 : 14);
    $mapEmbedUrl = 'https://maps.google.com/maps?q='.rawurlencode($mapQuery).'&z='.$mapZoom.'&output=embed';
    $mapOpenUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($mapQuery);
@endphp

<section class="bg-white border border-slate-200 rounded-xl p-5" aria-labelledby="property-location-heading">
    <div class="flex items-start justify-between gap-3 mb-3">
        <div>
            <h2 id="property-location-heading" class="font-semibold text-slate-900">Location</h2>
            <p class="text-xs text-slate-500 mt-1">
                {{ $hasPropertyCoordinates ? 'Map pin from the property coordinates.' : 'Approximate area based on the listing address.' }}
            </p>
        </div>
        <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide rounded-full px-2 py-1 {{ $hasPropertyCoordinates ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
            {{ $hasPropertyCoordinates ? 'Property pin' : 'Approximate' }}
        </span>
    </div>

    <iframe
        class="w-full h-56 rounded-lg border border-slate-200 bg-slate-100"
        src="{{ $mapEmbedUrl }}"
        title="{{ $hasPropertyCoordinates ? 'Map showing the property location' : 'Map showing the approximate property area' }}"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen></iframe>

    <div class="mt-3 flex items-center justify-between gap-3">
        <p class="text-xs text-slate-500 truncate">
            {{ collect([$property->address_line, $property->barangay?->name, $property->location?->city_municipality])->filter()->implode(', ') }}
        </p>
        <a href="{{ $mapOpenUrl }}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-xs font-semibold text-blue-600 hover:underline">
            Open in Google Maps
        </a>
    </div>
</section>
