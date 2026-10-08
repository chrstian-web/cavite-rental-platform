@extends('layouts.public')

@section('title', 'Find a place to call home · Cavite Rentals')
@section('body_class', 'landing')
@section('main_class', 'p-0')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ @filemtime(public_path('css/landing.css')) }}">
    <noscript><style>.step { opacity: 1 !important; transform: none !important; } .lp-bar i { width: var(--w) !important; }</style></noscript>
@endpush

@section('content')
@php
    $locations = \Illuminate\Support\Facades\Schema::hasTable('locations')
        ? \App\Models\Location::where('is_active', true)->orderBy('city_municipality')->get()
        : collect();
    $partnerUrl = auth()->check() && auth()->user()->isTenant() ? route('tenant.recommendations.create') : route('register');
    $ownerUrl   = auth()->check() && auth()->user()->isOwner() ? route('owner.properties.index') : route('register');

    // Reusable icons
    $iconHouse = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/></svg>';
    $iconCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/></svg>';
@endphp

{{-- ═══════════════ HERO ═══════════════ --}}
<section class="lp-hero">
    <div class="lp-stars"></div>
    <div class="lp-wrap">
        <h1>Listings change fast. Cavite Rentals keeps you ahead.</h1>
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

        {{-- Hero demo: a renter describes what they need, results are ranked --}}
        <div class="lp-demo-wrap">
            @include('partials.landing-mascots')
            <div class="lp-win" data-demo>
                <div class="lp-win-bar"><span class="lp-dots"><i></i><i></i><i></i></span><span class="lp-win-title">Find a place · Cavite Rentals</span></div>
                <div class="lp-win-body">
                    <div class="lp-user step" data-s="1" data-text="Boarding house in Dasmariñas under ₱5,000 with Wi-Fi, near a school"></div>
                    <div class="lp-ai step" data-s="2">
                        <span class="lp-av">{!! $iconHouse !!}</span>
                        <p>Found 3 listings that fit. They’re ranked by budget, location, and amenities.</p>
                    </div>
                    <div class="lp-results">
                        <div class="lp-res step" data-s="3" data-wait="250"><div class="lp-thumb"></div><b>Sunrise Boarding House</b><small>Dasmariñas · ₱4,500/mo</small><div class="lp-res-row"><span class="lp-bar" style="--w:94%"><i></i></span>94%</div></div>
                        <div class="lp-res step" data-s="4" data-wait="250"><div class="lp-thumb lp-thumb--b"></div><b>Greenfield Dorm</b><small>Dasmariñas · ₱4,800/mo</small><div class="lp-res-row"><span class="lp-bar" style="--w:88%"><i></i></span>88%</div></div>
                        <div class="lp-res step" data-s="5" data-wait="250"><div class="lp-thumb lp-thumb--c"></div><b>Casa Lucia Rooms</b><small>Dasmariñas · ₱3,900/mo</small><div class="lp-res-row"><span class="lp-bar" style="--w:81%"><i></i></span>81%</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════ FEATURES: compare + viewing ═══════════════ --}}
<section class="lp-sec" id="features">
    <div class="lp-wrap">
        <div class="lp-sec-head">
            <h2>Search, compare, and book a viewing in minutes.</h2>
            <p>Filter by location, property type, and monthly budget. Save favorites, compare listings side by side, and request a viewing without losing track.</p>
            <a href="{{ route('properties.index') }}" class="lp-link">Browse all properties →</a>
        </div>

        <div class="lp-demo lp-win" data-demo>
            <div class="lp-win-bar"><span class="lp-dots"><i></i><i></i><i></i></span><span class="lp-win-title">Compare · 2 saved listings</span></div>
            <div class="lp-split">
                <div>
                    <div class="lp-pane-title">Side by side</div>
                    <table class="lp-cmp">
                        <thead><tr><th></th><th class="win" data-s="3">Sunrise Boarding House</th><th>Imus Residences</th></tr></thead>
                        <tbody>
                            <tr><td>Rent</td><td class="win" data-s="3">₱4,500</td><td>₱6,200</td></tr>
                            <tr><td>City</td><td class="win" data-s="3">Dasmariñas</td><td>Imus</td></tr>
                            <tr><td>Wi-Fi</td><td class="win" data-s="3">Included</td><td>Not included</td></tr>
                            <tr><td>To school</td><td class="win" data-s="3">450 m</td><td>2.1 km</td></tr>
                            <tr><td>Match</td><td class="win" data-s="3">94%</td><td>78%</td></tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <div class="lp-pane-title">Assistant</div>
                    <div class="lp-user step" data-s="1" data-text="Compare my saved listings and book a viewing for the best match."></div>
                    <div class="lp-ai step" data-s="2" data-wait="900">
                        <span class="lp-av">{!! $iconHouse !!}</span>
                        <p>Sunrise Boarding House is within your ₱5,000 budget and a short walk from school.</p>
                    </div>
                    <div class="lp-chip step" data-s="4">
                        {!! $iconCheck !!}
                        <span><b>Viewing requested</b>Saturday, 10:00 AM · Sunrise Boarding House</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════ PARTNER: recommendation mode ═══════════════ --}}
<section class="lp-sec">
    <div class="lp-wrap">
        <div class="lp-sec-head">
            <h2>Your rental partner at every step.</h2>
            <p>Tell us your budget, location, and must-haves. Cavite Rentals scores every available listing against them and puts the best fits first.</p>
            <a href="{{ $partnerUrl }}" class="lp-link">Explore recommendations →</a>
        </div>

        <div class="lp-demo lp-win" data-demo>
            <div class="lp-win-bar">
                <span class="lp-dots"><i></i><i></i><i></i></span><span class="lp-win-title">Recommendations</span>
                <div class="lp-seg" data-s="1" data-wait="1000" aria-hidden="true"><i class="lp-seg-thumb"></i><span>Browse</span><span>Recommend</span></div>
            </div>
            <div class="lp-win-body">
                <div class="lp-user step" data-s="2" data-text="I work in Imus. I need a furnished room for 2, up to ₱8,000 a month."></div>
                <div class="lp-ai step" data-s="3" data-wait="500">
                    <span class="lp-av">{!! $iconHouse !!}</span>
                    <p>Scoring every available listing against what matters to you:</p>
                </div>
                <div class="lp-weights">
                    <div class="lp-weight step" data-s="4" data-wait="170"><span>Budget fit</span><span class="lp-bar" style="--w:100%"><i></i></span><em>30%</em></div>
                    <div class="lp-weight step" data-s="5" data-wait="170"><span>Location match</span><span class="lp-bar" style="--w:67%"><i></i></span><em>20%</em></div>
                    <div class="lp-weight step" data-s="6" data-wait="170"><span>Amenities</span><span class="lp-bar" style="--w:50%"><i></i></span><em>15%</em></div>
                    <div class="lp-weight step" data-s="7" data-wait="170"><span>Property type</span><span class="lp-bar" style="--w:33%"><i></i></span><em>10%</em></div>
                    <div class="lp-weight step" data-s="8" data-wait="170"><span>Room capacity</span><span class="lp-bar" style="--w:33%"><i></i></span><em>10%</em></div>
                    <div class="lp-weight step" data-s="9" data-wait="170"><span>Distance</span><span class="lp-bar" style="--w:33%"><i></i></span><em>10%</em></div>
                    <div class="lp-weight step" data-s="10" data-wait="500"><span>Furnishing</span><span class="lp-bar" style="--w:17%"><i></i></span><em>5%</em></div>
                </div>
                <div class="lp-done step" data-s="11">
                    <span><b>Top match: Imus Residences, 91%.</b> <span class="lp-muted">Ranked from best fit to lowest.</span></span>
                    <a href="{{ $partnerUrl }}" class="lp-btn lp-btn--primary" style="padding:.5rem 1rem;font-size:.85rem">See my matches</a>
                </div>
            </div>
        </div>

        <div class="lp-bento" style="margin-top:16px">
            <div class="lp-card lp-card--feat lp-span-4">
                <div class="lp-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 100 18 9 9 0 000-18z"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg></div>
                <h3>Walk through before you visit.</h3>
                <p>Open a 360° virtual tour from the listing and check the rooms before you commit to a trip.</p>
                <a href="{{ route('properties.index') }}" class="lp-link">Browse properties →</a>
            </div>
            <div class="lp-card lp-card--feat lp-card--blue lp-span-4">
                <div class="lp-ico lp-ico--blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M8.5 12l2.5 2.5L15.5 10"/></svg></div>
                <h3>Owners are verified.</h3>
                <p>Owners submit documents for verification, and new properties are reviewed before they go live.</p>
                <a href="{{ $ownerUrl }}" class="lp-link">List your property →</a>
            </div>
            <div class="lp-card lp-card--feat lp-span-4">
                <div class="lp-ico lp-ico--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18M7 15h4"/></svg></div>
                <h3>Pay rent online.</h3>
                <p>Check out securely or upload proof of payment, then keep every receipt in one place.</p>
                <a href="{{ route('register') }}" class="lp-link">Create an account →</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════ REPAIRS + DASHBOARD ═══════════════ --}}
<section class="lp-sec">
    <div class="lp-wrap">
        <div class="lp-bento">
            <div class="lp-card lp-card--blue lp-span-5">
                <h3>Fix problems fast.</h3>
                <p>Report a repair with photos, then follow it until it’s resolved. Owners see every request in one list.</p>
                <a href="{{ route('register') }}" class="lp-link">Start a request →</a>
                <div class="lp-mini" data-demo>
                    <div class="lp-req-top"><b>Leaking kitchen faucet</b><span class="lp-badge">High priority</span></div>
                    <div class="lp-photos"><i></i><i></i></div>
                    <div class="lp-stepper">
                        <span class="lp-node show" data-on>Submitted</span>
                        <span class="lp-line" data-s="1"></span>
                        <span class="lp-node" data-s="1">In progress</span>
                        <span class="lp-line" data-s="3"></span>
                        <span class="lp-node" data-s="3">Resolved</span>
                    </div>
                    <div class="lp-note step" data-s="2" data-wait="900">Owner replied: a plumber is scheduled for Friday.</div>
                    <div class="lp-note step" data-s="4" data-wait="1200">Marked resolved. You can add a note if anything is off.</div>
                </div>
            </div>

            <div class="lp-card lp-span-7">
                <h3>Plan with clarity.</h3>
                <p>Applications, contracts, and payments stay in one dashboard, for tenants and owners alike.</p>
                <a href="{{ route('register') }}" class="lp-link">See the dashboards →</a>
                <div class="lp-mini" data-demo>
                    <div class="lp-board">
                        <div class="lp-col">
                            <h4>Pending</h4>
                            <div class="lp-task step show" data-on data-off="1"><b>Unit 2B · J. Reyes</b>Application</div>
                            <div class="lp-task"><b>Room 5 · A. Cruz</b>Application</div>
                        </div>
                        <div class="lp-col">
                            <h4>Approved</h4>
                            <div class="lp-task lp-task--live step" data-s="1" data-off="2"><b>Unit 2B · J. Reyes</b>Approved</div>
                            <div class="lp-task"><b>Room 1 · M. Lim</b>Approved</div>
                        </div>
                        <div class="lp-col">
                            <h4>Contract</h4>
                            <div class="lp-task lp-task--live step" data-s="2" data-wait="1600"><b>Unit 2B · J. Reyes</b>Contract active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════ TESTIMONIAL + OWNERS ═══════════════ --}}
<section class="lp-sec">
    <div class="lp-wrap">
        {{-- Placeholder testimonial: replace with a real tenant quote. --}}
        <figure class="lp-quote">
            <blockquote>“I compared three boarding houses on my phone, toured one in 360°, and booked a viewing the same night. I never had to message anyone to ask what was still available.”</blockquote>
            <figcaption><span class="lp-avatar">MS</span><span><b>Maria Santos</b>Tenant, Dasmariñas</span></figcaption>
        </figure>

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
        @include('partials.landing-mascots', ['mod' => 'lp-land-group'])
    </div>
</section>

<footer class="lp-foot">
    <div class="lp-wrap">
        <h2>Footnotes</h2>
        <ol><li>Listings, rent, and availability vary by property and are set by each owner.</li></ol>
    </div>
</footer>

<script>
(() => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const steps = '[data-s],[data-off]';

    async function typeInto(el) {
        el.classList.add('lp-typing');
        el.textContent = '';
        for (const ch of el.dataset.text) { el.textContent += ch; await sleep(22); }
        el.classList.remove('lp-typing');
    }

    function reset(root) {
        root.querySelectorAll(steps + ',[data-on]').forEach(el => {
            el.classList.toggle('show', el.hasAttribute('data-on'));
            if (el.dataset.text !== undefined) el.textContent = '';
        });
    }

    async function play(root, alive) {
        const nums = [...new Set([...root.querySelectorAll(steps)]
            .flatMap(e => [e.dataset.s, e.dataset.off]).filter(Boolean).map(Number))].sort((a, b) => a - b);
        for (const n of nums) {
            if (!alive()) return;
            const off = [...root.querySelectorAll(`[data-off="${n}"]`)];
            const on = [...root.querySelectorAll(`[data-s="${n}"]`)];
            off.forEach(e => e.classList.remove('show'));
            on.forEach(e => e.classList.add('show'));
            for (const e of on) if (e.dataset.text !== undefined) await typeInto(e);
            await sleep(+((on[0] || off[0]).dataset.wait || 700));
        }
    }

    document.querySelectorAll('[data-demo]').forEach(root => {
        if (reduce) {
            // Static final state for people who prefer reduced motion.
            root.querySelectorAll(steps).forEach(e => {
                if (!e.hasAttribute('data-off')) e.classList.add('show');
                if (e.dataset.text !== undefined) e.textContent = e.dataset.text;
            });
            return;
        }
        let visible = false, running = false;
        async function loop() {
            running = true;
            while (visible) {
                reset(root);
                await sleep(600);
                await play(root, () => visible);
                if (!visible) break;
                await sleep(4500);
            }
            running = false;
        }
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            if (visible && !running) loop();
        }, { threshold: 0.4 }).observe(root);
    });

    // Closing mascots drop into place once, when scrolled into view.
    const land = document.querySelector('.lp-land-group');
    if (land) {
        if (reduce) land.classList.add('in');
        else new IntersectionObserver((es, io) => {
            if (es[0].isIntersecting) { land.classList.add('in'); io.disconnect(); }
        }, { threshold: 0.5 }).observe(land);
    }
})();
</script>
@endsection
