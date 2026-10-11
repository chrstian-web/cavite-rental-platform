@extends('layouts.public')

@section('title', 'Find a place to call home · Cavite Rentals')
@section('body_class', 'landing')
@section('main_class', 'p-0')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ @filemtime(public_path('css/landing.css')) }}">
    <script src="{{ asset('js/landing.js') }}?v={{ @filemtime(public_path('js/landing.js')) }}" defer></script>
@endpush

@section('content')
@php
    $locations = \Illuminate\Support\Facades\Schema::hasTable('locations')
        ? \App\Models\Location::where('is_active', true)->orderBy('city_municipality')->get()
        : collect();
    // Logged-in users must never be sent to /register (it only bounces them back through the guest middleware).
    $signedIn    = auth()->check();
    $fallbackUrl = $signedIn ? route('dashboard') : route('register');
    $partnerUrl  = $signedIn && auth()->user()->isTenant() ? route('tenant.recommendations.create') : $fallbackUrl;
    $ownerUrl    = $signedIn && in_array(auth()->user()->role?->slug, ['owner', 'manager', 'super_admin'], true) ? route('owner.properties.index') : $fallbackUrl;
    $payUrl      = $signedIn && auth()->user()->isTenant() ? route('tenant.payments.index') : $fallbackUrl;
    $repairUrl   = $signedIn && auth()->user()->isTenant() ? route('tenant.maintenance.index') : $fallbackUrl;

    // Interactive 3D scene shown in the hero. To use your own, export a Spline scene with the
    // "Spline Viewer" option and paste its .splinecode URL here.
    $heroScene = 'https://prod.spline.design/kZDDjO5HuC9GJUM2/scene.splinecode';
@endphp

{{-- ═══════════════ HERO ═══════════════ --}}
<section class="lp-hero">
    <div class="lp-stars"></div>
    <div class="lp-wrap lp-hero-grid">
        <div class="lp-hero-copy">
            <p class="lp-eyebrow">Rental homes · Boarding houses · Dormitories</p>
            <h1>Listings change fast. <span class="lp-grad">Cavite Rentals</span> keeps you ahead.</h1>
            <p class="lp-sub">Discover rental homes, boarding houses, and dormitories across Cavite. Compare your options, schedule viewings, and move forward with confidence.</p>

            <form method="GET" action="{{ route('properties.index') }}" class="lp-search">
                <div class="lp-search-box">
                    <select name="location_id" aria-label="Location">
                        <option value="">Any location</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->city_municipality }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="max_rent" min="0" placeholder="Max ₱ / month" aria-label="Maximum monthly rent">
                </div>
                <button type="submit" class="lp-btn lp-btn--primary">Explore properties</button>
                @guest
                    <a href="{{ route('register') }}" class="lp-btn lp-btn--ghost">List your property</a>
                @endguest
            </form>
        </div>

        {{-- Interactive 3D stage. The mascots are the fallback on phones, reduced motion, and when WebGL or the CDN is unavailable. --}}
        <div class="lp-stage lp-spot" data-spline data-scene="{{ $heroScene }}" aria-hidden="true">
            <div class="lp-stage-fallback">
                @include('partials.landing-mascots')
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════ FEATURES ═══════════════ --}}
<section class="lp-sec" id="features">
    <div class="lp-wrap">
        <div class="lp-sec-head">
            <h2>Everything you need to rent in Cavite.</h2>
            <p>From the first search to the monthly payment, each step happens in one place.</p>
        </div>

        <div class="lp-bento">
            <div class="lp-spot lp-card lp-card--feat lp-span-4">
                <div class="lp-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></div>
                <h3>Search, compare, and book a viewing.</h3>
                <p>Filter by location, property type, and monthly budget. Save favorites, compare listings side by side, and request a viewing.</p>
                <a href="{{ route('properties.index') }}" class="lp-link">Browse properties →</a>
            </div>

            <div class="lp-spot lp-card lp-card--feat lp-card--blue lp-span-4">
                <div class="lp-ico lp-ico--blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6l-5.4 2.9 1.2-6L3.3 9.3l6.1-.7L12 3z"/></svg></div>
                <h3>Recommendations that fit you.</h3>
                <p>Tell us your budget, location, and must-haves. Every available listing is scored against them and the best fits come first.</p>
                <a href="{{ $partnerUrl }}" class="lp-link">Explore recommendations →</a>
            </div>

            <div class="lp-spot lp-card lp-card--feat lp-span-4">
                <div class="lp-ico lp-ico--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 100 18 9 9 0 000-18z"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg></div>
                <h3>Walk through before you visit.</h3>
                <p>Open a 360° virtual tour from the listing and check the rooms before you commit to a trip.</p>
                <a href="{{ route('properties.index') }}" class="lp-link">Browse properties →</a>
            </div>

            <div class="lp-spot lp-card lp-card--feat lp-card--blue lp-span-4">
                <div class="lp-ico lp-ico--blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M8.5 12l2.5 2.5L15.5 10"/></svg></div>
                <h3>Owners are verified.</h3>
                <p>Owners submit documents for verification, and new properties are reviewed before they go live.</p>
                <a href="{{ $ownerUrl }}" class="lp-link">List your property →</a>
            </div>

            <div class="lp-spot lp-card lp-card--feat lp-span-4">
                <div class="lp-ico lp-ico--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18M7 15h4"/></svg></div>
                <h3>Pay rent online.</h3>
                <p>Check out securely or upload proof of payment, then keep every receipt in one place.</p>
                <a href="{{ $payUrl }}" class="lp-link">{{ $signedIn ? 'View my payments' : 'Create an account' }} →</a>
            </div>

            <div class="lp-spot lp-card lp-card--feat lp-span-4">
                <div class="lp-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg></div>
                <h3>Fix problems fast.</h3>
                <p>Report a repair with photos, then follow it until it’s resolved. Owners see every request in one list.</p>
                <a href="{{ $repairUrl }}" class="lp-link">{{ $signedIn ? 'Maintenance requests' : 'Start a request' }} →</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════ OWNERS ═══════════════ --}}
<section class="lp-sec">
    <div class="lp-wrap">
        <div class="lp-owner">
            <p>Owners get the same clarity: list your property, review applications, and manage contracts, payments, and repairs with tools that adapt to your place.</p>
            <a href="{{ $ownerUrl }}" class="lp-link">Start listing →</a>
        </div>
    </div>
</section>

{{-- ═══════════════ CLOSING ═══════════════ --}}
<section class="lp-close" id="lp-close">
    <div class="lp-stars"></div>
    <div class="lp-wrap">
        <h2 class="lp-h2">Renters and owners across Cavite call us home.</h2>
        <p>Whether you’re looking for your first boarding house or managing your tenth unit, Cavite Rentals is where you belong. Join the platform built for how Cavite rents.</p>
        <div class="lp-cta">
            <a href="{{ route('properties.index') }}" class="lp-btn lp-btn--primary">Explore properties</a>
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
