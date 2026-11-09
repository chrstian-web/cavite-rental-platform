@extends('layouts.public')

@section('title', 'Find a place to rent in Cavite · Cavite Rentals')
@section('body_class', 'landing')
@section('main_class', 'p-0')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ @filemtime(public_path('css/landing.css')) }}">
    <script src="{{ asset('js/landing.js') }}?v={{ @filemtime(public_path('js/landing.js')) }}" defer></script>
    <noscript><style>.lp-map, .lp-map-foot { display: none; } .lp-citylist { display: block; }</style></noscript>
@endpush

@section('content')
@php
    $locations = \Illuminate\Support\Facades\Schema::hasTable('locations')
        ? \App\Models\Location::where('is_active', true)->orderBy('city_municipality')->get()
        : collect();
    $landing = \App\Services\LandingData::get();
    $cities  = $landing['cities'];
    $hasData = $landing['total'] > 0;

    // Logged-in users must never be sent to /register (it only bounces them back through the guest middleware).
    $signedIn    = auth()->check();
    $isTenant    = $signedIn && auth()->user()->isTenant();
    $fallbackUrl = $signedIn ? route('dashboard') : route('register');
    $partnerUrl  = $isTenant ? route('tenant.recommendations.create') : $fallbackUrl;
    $ownerUrl    = $signedIn && in_array(auth()->user()->role?->slug, ['owner', 'manager', 'super_admin'], true) ? route('owner.properties.index') : $fallbackUrl;
    $applyUrl    = $isTenant ? route('tenant.applications.index') : $fallbackUrl;
    $payUrl      = $isTenant ? route('tenant.payments.index') : $fallbackUrl;
    $repairUrl   = $isTenant ? route('tenant.maintenance.index') : $fallbackUrl;

    $topCity = $cities[0] ?? null;
    $listingWord = fn (int $n) => number_format($n).' '.($n === 1 ? 'listing' : 'listings');
@endphp

{{-- ═══════════════ HERO ═══════════════ --}}
<section class="lp-hero">
    <div class="lp-wrap lp-hero-grid">
        <div class="lp-hero-copy">
            <h1>Boarding houses, condos, and dorms in Cavite, priced up front.</h1>
            <p class="lp-sub">Compare verified listings, take a 360° tour, and book a viewing before you travel.</p>

            <form method="GET" action="{{ route('properties.index') }}" class="lp-find" data-search data-count-url="{{ route('properties.count') }}">
                <fieldset class="lp-types">
                    <legend class="lp-sr">Property type (choose one, or none to see all)</legend>
                    <label class="lp-chip"><input type="checkbox" name="type" value="boarding_house"><span>Boarding house</span></label>
                    <label class="lp-chip"><input type="checkbox" name="type" value="condominium"><span>Condominium</span></label>
                    <label class="lp-chip"><input type="checkbox" name="type" value="dormitory"><span>Dormitory</span></label>
                </fieldset>

                <div class="lp-find-row">
                    <label class="lp-field">
                        <span class="lp-sr">Location</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                        <select name="location_id">
                            <option value="">Any location</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->city_municipality }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="lp-field">
                        <span class="lp-sr">Maximum monthly rent in pesos</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 8v8M9.5 8h3.2a2 2 0 010 4H9.5M8 10.5h6"/></svg>
                        <input type="number" name="max_rent" min="0" step="500" inputmode="numeric" placeholder="Max rent (₱)">
                    </label>
                    <button type="submit" class="lp-btn lp-btn--primary" data-submit>
                        @if ($hasData)
                            Search <span data-count>{{ number_format($landing['total']) }}</span> <span data-noun>{{ $landing['total'] === 1 ? 'listing' : 'listings' }}</span>
                        @else
                            Search listings
                        @endif
                    </button>
                </div>
                <p class="lp-sr" role="status" aria-live="polite" data-status></p>
            </form>

            <p class="lp-aside">
                @if ($isTenant)
                    Not sure where to start? <a href="{{ $partnerUrl }}" class="lp-link">Get recommendations</a>
                @elseif ($signedIn)
                    Looking to rent out a place? <a href="{{ $ownerUrl }}" class="lp-link">Go to my properties</a>
                @else
                    Own a property? <a href="{{ route('register') }}" class="lp-link">List it on Cavite Rentals</a>
                @endif
            </p>
        </div>

        {{-- Price map: real city positions, real lowest rent per city. --}}
        <div class="lp-map-card" data-map-card data-browse="{{ route('properties.index') }}">
            <div class="lp-map-head">
                <h2>Starting rent by city</h2>
                <span>per month</span>
            </div>

            @if ($topCity)
                <div class="lp-map" data-map data-cities="{{ json_encode($cities, JSON_UNESCAPED_UNICODE) }}" role="group" aria-label="Map of Cavite with the lowest monthly rent in each city"></div>

                <ul class="lp-citylist">
                    @foreach ($cities as $c)
                        <li>
                            <a href="{{ route('properties.index', ['location_id' => $c['id']]) }}">
                                <span>{{ $c['name'] }}</span>
                                <span>{{ $listingWord($c['listings']) }}@if ($c['label']) · from {{ $c['label'] }}@endif</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="lp-map-foot">
                    <div>
                        <strong data-sel-name>{{ $topCity['name'] }}</strong>
                        <span data-sel-meta>{{ $listingWord($topCity['listings']) }}@if ($topCity['label']) · from {{ $topCity['label'] }}@endif</span>
                    </div>
                    <a href="{{ route('properties.index', ['location_id' => $topCity['id']]) }}" class="lp-link" data-sel-link>View listings</a>
                </div>
            @else
                <div class="lp-map-empty">
                    <p>Verified listings will appear on this map as owners publish them.</p>
                    <a href="{{ $ownerUrl }}" class="lp-link">List your property</a>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ═══════════════ NUMBERS ═══════════════ --}}
@if ($hasData)
    <section class="lp-stats" aria-label="Cavite Rentals at a glance">
        <div class="lp-wrap lp-stats-grid">
            <div><strong>{{ number_format($landing['total']) }}</strong><span>verified {{ $landing['total'] === 1 ? 'listing' : 'listings' }}</span></div>
            <div><strong>{{ count($cities) }}</strong><span>{{ count($cities) === 1 ? 'city' : 'cities and towns' }} with listings</span></div>
            <div><strong>{{ number_format($landing['tours']) }}</strong><span>with a 360° tour</span></div>
            @if ($landing['lowest'])
                <div><strong>₱{{ number_format($landing['lowest']) }}</strong><span>lowest monthly rent</span></div>
            @endif
        </div>
    </section>
@endif

{{-- ═══════════════ VERIFIED LISTINGS ═══════════════ --}}
@if (count($landing['featured']))
    <section class="lp-sec">
        <div class="lp-wrap">
            <div class="lp-sec-row">
                <h2 class="lp-h2">Verified listings</h2>
                <a href="{{ route('properties.index') }}" class="lp-link">Browse all properties</a>
            </div>
            <div class="lp-listings">
                @foreach ($landing['featured'] as $p)
                    <a href="{{ route('properties.show', $p['slug']) }}" class="lp-listing">
                        <div class="lp-listing-img">
                            @if ($p['image'])
                                <img src="{{ $p['image'] }}" alt="" loading="lazy" width="400" height="300">
                            @else
                                <span class="lp-noimg">No photo yet</span>
                            @endif
                            <span class="lp-badge lp-badge--ok">Verified</span>
                            @if ($p['tour'])<span class="lp-badge lp-badge--tour">360° tour</span>@endif
                        </div>
                        <div class="lp-listing-body">
                            <h3>{{ $p['name'] }}</h3>
                            <p class="lp-listing-meta">{{ $p['type'] }}@if ($p['city']) · {{ $p['city'] }}@endif</p>
                            <p class="lp-listing-price">{{ $p['price'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ═══════════════ JOURNEY ═══════════════ --}}
<section class="lp-sec" id="how-it-works">
    <div class="lp-wrap">
        <h2 class="lp-h2 lp-h2--left">From first search to monthly rent, in one place</h2>
        <ol class="lp-steps">
            <li>
                <a href="{{ route('properties.index') }}">
                    <span class="lp-step-n">1</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                    <h3>Search and compare</h3>
                    <p>Filter by city, property type, and budget. Save favorites and compare side by side.</p>
                </a>
            </li>
            <li>
                <a href="{{ route('properties.index') }}">
                    <span class="lp-step-n">2</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 100 18 9 9 0 000-18z"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>
                    <h3>Tour and view</h3>
                    <p>Walk through rooms in 360°, then request a viewing.</p>
                </a>
            </li>
            <li>
                <a href="{{ $applyUrl }}">
                    <span class="lp-step-n">3</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>
                    <h3>Apply and sign</h3>
                    <p>Upload your documents, then review and sign the contract online.</p>
                </a>
            </li>
            <li>
                <a href="{{ $payUrl }}">
                    <span class="lp-step-n">4</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18M7 15h4"/></svg>
                    <h3>Pay rent online</h3>
                    <p>Check out securely or upload proof of payment. Every receipt is kept for you.</p>
                </a>
            </li>
            <li>
                <a href="{{ $repairUrl }}">
                    <span class="lp-step-n">5</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                    <h3>Request repairs</h3>
                    <p>Report a problem with photos and follow it until it's fixed.</p>
                </a>
            </li>
        </ol>
    </div>
</section>

{{-- ═══════════════ OWNERS ═══════════════ --}}
<section class="lp-sec">
    <div class="lp-wrap">
        <div class="lp-owner">
            <div>
                <h2 class="lp-h2 lp-h2--left">Own a property in Cavite?</h2>
                <p>List it, review applications, and manage contracts, payments, and repairs from one dashboard. Owners submit documents for verification, and every new property is reviewed before it goes live.</p>
            </div>
            <a href="{{ $ownerUrl }}" class="lp-btn lp-btn--ghost">{{ $signedIn ? 'Go to my properties' : 'List your property' }}</a>
        </div>
    </div>
</section>

{{-- ═══════════════ CLOSING ═══════════════ --}}
<section class="lp-close">
    <div class="lp-wrap">
        <h2 class="lp-h2">Find your next place in Cavite.</h2>
        <p>Whether it's your first boarding house or your tenth unit, everything from the first search to the monthly payment happens here.</p>
        <div class="lp-cta">
            <a href="{{ route('properties.index') }}" class="lp-btn lp-btn--primary">Browse properties</a>
            @guest<a href="{{ route('register') }}" class="lp-btn lp-btn--ghost">Create an account</a>@endguest
        </div>
    </div>
</section>

<footer class="lp-foot">
    <div class="lp-wrap">
        <p>Listings, rent, and availability vary by property and are set by each owner.</p>
    </div>
</footer>
@endsection
