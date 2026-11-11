@extends('layouts.app')

@section('title', 'Renter Dashboard')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <style>
        .rent-map-shell { background: #0b1620; border: 1px solid #263746; }
        .rent-map-shell details[open] { z-index: 1100; }
        .rent-map-shell details[open] > div { z-index: 1101; }
        .rent-map { position: relative; z-index: 0; isolation: isolate; height: 240px; min-height: 200px; border-radius: 1rem; overflow: hidden; background: #172631; }
        /* Leaflet panes/controls use z-index 400-1000; isolation keeps them inside the map so the sticky nav (z-60) and its dropdowns stay on top. */
        .rent-map .leaflet-tile { filter: brightness(.72) saturate(.75) hue-rotate(155deg); }
        .rent-map .leaflet-control-zoom { border: 0; box-shadow: 0 8px 20px rgb(2 6 23 / .35); }
        .rent-map .leaflet-control-zoom a { background: #0b1b29; color: #f8fafc; border-color: #304657; }
        .rent-map .leaflet-control-zoom a:hover { background: #183247; }
        .rent-map-pin { display: grid; place-items: center; min-width: 42px; height: 28px; padding: 0 9px; border-radius: 999px; background: #e11d48; border: 2px solid rgba(255,255,255,.9); color: #fff; font-size: 12px; font-weight: 800; box-shadow: 0 5px 12px rgb(2 6 23 / .35); white-space: nowrap; animation: rent-map-pin-drop .55s cubic-bezier(.2,.8,.2,1) both; animation-delay: calc(var(--pin-index, 0) * 75ms); }
        .rent-map-pin--small { background: #2563eb; }
        @keyframes rent-map-pin-drop { from { opacity: 0; transform: translateY(-18px) scale(.88); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @media (prefers-reduced-motion: reduce) { .rent-map-pin { animation: none; } }
        .rent-map .leaflet-popup-content-wrapper, .rent-map .leaflet-popup-tip { background: #10202d; color: #e2e8f0; }
        .rent-map .leaflet-popup-content { margin: 14px 16px; }
        .rent-map .leaflet-popup-close-button { color: #cbd5e1 !important; }
        .rent-map-popup a { color: #93c5fd; font-weight: 700; text-decoration: none; }
        .rent-map-popup a:hover { text-decoration: underline; }
    </style>
@endpush

@section('content')
    <div class="mb-8"><p class="eyebrow text-rose-600 mb-2">Renter dashboard</p><h1 class="text-3xl font-extrabold tracking-tight text-slate-950">Welcome, {{ auth()->user()->first_name }}</h1><p class="text-sm text-slate-500 mt-1">Stay on top of your rental journey in Cavite.</p></div>

    @include('partials.onboarding-checklist')

    @if ($showNearbyPrompt ?? false)
        <div id="nearby-location-prompt" role="dialog" aria-modal="true" aria-labelledby="nearby-prompt-title"
             style="position:fixed;inset:0;z-index:9997;background:rgba(2,6,23,.55);display:flex;align-items:center;justify-content:center;padding:16px">
            <div class="surface-card p-6 sm:p-8 w-full" style="max-width:440px">
                <h2 id="nearby-prompt-title" class="text-xl font-extrabold text-slate-950">Share location &amp; find nearby homes</h2>
                <p class="text-sm text-slate-500 mt-2">Tell us your city, barangay, or street and we will show rentals close to you.</p>
                <form method="POST" action="{{ route('properties.nearby') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="text" name="location" required minlength="2" maxlength="120" placeholder="City, barangay, or street"
                           class="w-full rounded-lg border border-slate-300 text-sm">
                    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                        <button type="button" class="rt-soft inline-flex min-h-10 items-center justify-center rounded-lg border px-3 py-2 text-xs font-semibold"
                                onclick="document.getElementById('nearby-location-prompt').remove()">Not now</button>
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700">Find nearby homes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">
        @if ($availableProperties->isNotEmpty())
            <section class="lg:col-span-2 min-w-0">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-extrabold text-slate-950">Homes available to rent</h2>
                    <a href="{{ route('properties.index') }}" class="text-sm text-rose-600 hover:underline font-bold">View all →</a>
                </div>
                <div class="flex gap-4 overflow-x-auto pb-2 -mx-1 px-1 snap-x snap-mandatory">
                    @foreach ($availableProperties as $property)
                        <div class="w-64 sm:w-72 shrink-0 snap-start">
                            @include('partials.property-card', ['property' => $property])
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <div class="hidden lg:block lg:col-span-2"></div>
        @endif

    <section class="lg:col-span-1 min-w-0 rent-map-shell rounded-3xl p-4 sm:p-5 text-white shadow-xl shadow-slate-200/50">
        <div class="mb-4 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-extrabold">Starting rent by city</p>
                <p class="text-xs text-slate-400 mt-1">Enter a city, barangay, or street below to find matching rentals.</p>
                </div>
                <div class="shrink-0">
                    @include('partials.nav-search', ['navSearchButtonClass' => 'list-none cursor-pointer inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 select-none [&::-webkit-details-marker]:hidden', 'navSearchLabelClass' => 'inline'])
                </div>
            </div>
                <form method="GET" action="{{ route('properties.index') }}" class="grid w-full grid-cols-[minmax(0,1fr)_auto] items-stretch gap-2">
                    <input type="hidden" name="nearby" value="1">
                    <label class="sr-only" for="dashboard-manual-location">Enter your location</label>
                    <input id="dashboard-manual-location" name="location" type="text" list="dashboard-location-options" value="{{ session('nearby_search.location') }}" placeholder="City, barangay, or street" minlength="2" maxlength="120" required
                        class="w-full min-w-0 rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white placeholder:text-slate-400 focus:border-blue-400 focus:ring-blue-400">
                    <datalist id="dashboard-location-options">
                        @foreach ($rentMapLocations as $location)
                            <option value="{{ $location['city'] }}"></option>
                        @endforeach
                    </datalist>
                    <button type="submit" class="whitespace-nowrap rounded-lg bg-rose-600 px-3 py-2 text-sm font-bold text-white hover:bg-rose-700">Share location</button>
                </form>
        </div>
        <div id="tenant-rent-map" class="rent-map" aria-label="Map of available rental listings by city"></div>
        <div id="tenant-rent-map-list" class="mt-3 max-h-40 overflow-y-auto divide-y divide-slate-700/70">
            @foreach ($rentMapLocations as $location)
                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                    <div>
                        <strong class="text-white">{{ $location['city'] }}</strong>
                        <span class="text-slate-400">{{ $location['count'] }} {{ $location['count'] === 1 ? 'listing' : 'listings' }}</span>
                        @if ($location['min_rent'] !== null)
                            <span class="ml-1 whitespace-nowrap text-slate-300">· from ₱{{ number_format($location['min_rent']) }}</span>
                        @endif
                    </div>
                    @if (!empty($location['properties'][0]['url']))
                        <a class="text-blue-300 hover:text-white font-bold whitespace-nowrap" href="{{ $location['properties'][0]['url'] }}">View →</a>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($rentMapLocations->isEmpty())
            <p class="text-sm text-slate-400 mt-3">Verified listings will appear here when available. Pins use city-center locations when a property has no exact coordinates.</p>
        @endif
    </section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="surface-card p-6"><p class="eyebrow mb-3">Current rental</p>
                @if ($activeContract)
    @if ($activeContract->property)
        <p class="text-lg text-slate-950 font-extrabold">{{ $activeContract->property->name }} <span class="text-slate-400 font-normal">— {{ $activeContract->rentalSpace->space_number }}</span></p>
        <p class="text-sm text-slate-500 mt-1">₱{{ number_format($activeContract->monthly_rent) }}/month · until {{ $activeContract->end_date->format('M j, Y') }}</p>
        <a href="{{ route('tenant.contracts.show', $activeContract) }}" class="text-sm text-rose-600 hover:underline font-bold mt-3 inline-block">View contract →</a>
    @else
        <p class="text-sm text-slate-400">Property details unavailable.</p>
    @endif
@else
    <p class="text-sm text-slate-400">No active rental yet.</p>
@endif
            </div>

            @if ($activeContract && $rentSchedule)
                @include('partials.rent-schedule', ['contract' => $activeContract, 'schedule' => $rentSchedule])
            @endif

            <div class="surface-card p-6"><div class="flex justify-between items-center mb-3"><p class="eyebrow">Recent applications</p><a href="{{ route('tenant.applications.index') }}" class="text-xs font-bold text-rose-600">View all</a></div>
                @forelse ($recentApplications as $application)
                    <div class="flex items-center justify-between text-sm py-3 border-b border-slate-100 last:border-0"><span class="text-slate-700 font-medium">{{ $application->property->name ?? 'Property unavailable' }}</span><span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ match($application->status) { 'approved' => 'bg-green-50 text-green-700', 'rejected', 'cancelled' => 'bg-red-50 text-red-700', default => 'bg-amber-50 text-amber-700' } }}">{{ str($application->status)->replace('_',' ')->title() }}</span></div>
                @empty <p class="text-sm text-slate-400">No applications yet.</p> @endforelse
            </div>

            <div class="surface-card p-6"><p class="eyebrow mb-3">Upcoming viewings</p>
                @forelse ($upcomingViewings as $viewing)
                    <div class="flex items-center justify-between text-sm py-3 border-b border-slate-100 last:border-0"><span class="text-slate-700 font-medium">{{ $viewing->property->name ?? 'Property unavailable' }}</span><span class="text-slate-400">{{ $viewing->preferred_date->format('M j') }} at {{ $viewing->preferred_time }}</span></div>
                @empty <p class="text-sm text-slate-400">No upcoming viewings.</p> @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-card p-6"><p class="eyebrow mb-2">Upcoming payment</p>@if ($upcomingPayment)<p class="text-2xl font-extrabold text-slate-950">₱{{ number_format($upcomingPayment->amount, 2) }}</p><p class="text-xs text-slate-400 mt-1">Due {{ $upcomingPayment->due_date->format('M j, Y') }}</p>@else<p class="text-sm text-slate-400">Nothing due right now.</p>@endif</div>
            <div class="rounded-2xl p-6 text-white bg-gradient-to-br from-rose-600 to-orange-500 shadow-lg shadow-rose-200"><p class="text-sm font-bold mb-1">Find a better fit</p><p class="text-xs text-rose-100 mb-4">Get properties scored against your exact preferences.</p><a href="{{ route('tenant.recommendations.create') }}" class="inline-block bg-white text-rose-700 text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-rose-50 transition">Get recommendations</a></div>
            <div class="surface-card p-6"><p class="eyebrow mb-1">Favorites</p><p class="text-3xl font-extrabold text-slate-950">{{ $favoritesCount }}</p><a href="{{ route('tenant.favorites.index') }}" class="text-sm text-rose-600 hover:underline font-bold">View favorites →</a></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locations = @json($rentMapLocations->values());
            const mapElement = document.getElementById('tenant-rent-map');
            if (!mapElement || !window.L || !locations.length) return;

            const validLocations = locations.filter(location =>
                Number.isFinite(Number(location.latitude)) && Number.isFinite(Number(location.longitude))
            );
            if (!validLocations.length) return;

            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const touchPointer = window.matchMedia('(pointer: coarse)').matches;
            const map = L.map(mapElement, {
                scrollWheelZoom: false,
                dragging: !touchPointer,
                touchZoom: true,
                zoomControl: true,
                zoomAnimation: !reduceMotion,
                fadeAnimation: !reduceMotion,
                markerZoomAnimation: !reduceMotion,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[char]);

            validLocations.forEach((location, index) => {
                const rent = location.min_rent !== null && location.min_rent !== ''
                    ? '₱' + Number(location.min_rent).toLocaleString('en-PH')
                    : 'View';
                const icon = L.divIcon({
                    className: '',
                    html: '<span class="rent-map-pin ' + (index > 0 ? 'rent-map-pin--small' : '') + '" style="--pin-index:' + index + '">' + escapeHtml(rent) + '</span>',
                    iconSize: [58, 30],
                    iconAnchor: [29, 15],
                    popupAnchor: [0, -13]
                });
                const propertyLinks = (location.properties || []).map(property =>
                    '<li><a href="' + escapeHtml(property.url) + '">' + escapeHtml(property.name) + '</a>' +
                    (property.min_rent ? ' <span>· ₱' + Number(property.min_rent).toLocaleString() + '</span>' : '') + '</li>'
                ).join('');
                const popup = '<div class="rent-map-popup"><strong>' + escapeHtml(location.city) + '</strong>' +
                    '<p style="margin:4px 0 8px;color:#94a3b8;">' + location.count + ' listing' + (location.count === 1 ? '' : 's') + '</p>' +
                    '<ul style="margin:0;padding-left:16px;line-height:1.7;">' + propertyLinks + '</ul></div>';
                L.marker([location.latitude, location.longitude], { icon }).addTo(map).bindPopup(popup);

            });

            map.fitBounds(L.latLngBounds(validLocations.map(location => [location.latitude, location.longitude])), {
                padding: [50, 50],
                maxZoom: 11,
            });

            const invalidateMapSize = () => requestAnimationFrame(() => map.invalidateSize({ pan: false }));
            if ('ResizeObserver' in window) {
                new ResizeObserver(invalidateMapSize).observe(mapElement);
            } else {
                window.addEventListener('resize', invalidateMapSize, { passive: true });
            }
            invalidateMapSize();
        });
    </script>
@endpush
