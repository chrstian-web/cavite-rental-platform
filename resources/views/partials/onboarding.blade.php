{{--
    Welcome popup + guided tour for first-time renters and owners (dashboard only).
    Plain JavaScript, no library and no build step. Targets are found by [data-tour] or a CSS
    selector; a step whose target is missing or hidden (for example desktop-only menus on a
    phone) is skipped automatically.
--}}
@php
    $obUser = auth()->user();
    $obIsTenant = $obUser->isTenant();
    $obIsOwner = $obUser->isOwner();
    $obForceTour = request()->boolean('tour');
    $obShowWelcome = $obUser->onboarding_welcomed_at === null && ! $obForceTour;

    $obSteps = $obIsTenant ? [
        ['sel' => '.rt-hero', 'title' => 'This is your home base', 'body' => 'Your current rental, next payment and upcoming viewings are all shown here.'],
        ['sel' => '[data-tour="checklist"]', 'title' => 'Your getting-started checklist', 'body' => 'Only your phone number is required. Open any other step to learn how it works, and it ticks off.'],
        ['sel' => '[data-tour="homes"]', 'title' => 'Homes you can rent', 'body' => 'Scroll sideways to browse. Open a home to see photos, a virtual tour and its available units.'],
        ['sel' => '[data-tour="nav-search"]', 'title' => 'Search', 'body' => 'Look for a place by area, price or type. You can filter and sort on the results page.'],
        ['sel' => 'nav a[href*="/tenant/recommendations"]', 'title' => 'Recommended for you', 'body' => 'Tell us your budget and what you need, and we rank the homes that fit you best.'],
        ['sel' => 'nav a[href*="/tenant/favorites"]', 'title' => 'Favorites', 'body' => 'Homes you save with the heart icon are kept here so you can compare them.'],
        ['sel' => '[data-tour="nav-my-rentals"]', 'title' => 'My Rentals', 'body' => 'Your applications, viewings, contracts, payments and maintenance requests are all in this menu.'],
        ['sel' => '[data-tour="nav-menu"]', 'title' => 'Menu', 'body' => 'Tap here to open every page: favorites, applications, viewings, contracts and payments.'],
        ['sel' => '[data-tour="notifications"]', 'title' => 'Notifications', 'body' => 'Updates on your applications, viewings and payments show up here. A red dot means something new.'],
        ['sel' => '[data-tour="profile"]', 'title' => 'Your account', 'body' => 'Edit your profile and password here. You can also replay this tour from this menu.'],
        ['sel' => null, 'title' => 'You are ready', 'body' => 'Start with the checklist, or tap Search to find your first home. Good luck!'],
    ] : [
        ['sel' => '[data-tour="owner-verification"]', 'title' => 'Start here: verification', 'body' => 'An admin has to verify your documents before the property tools unlock. Submit them first.'],
        ['sel' => '.rt-hero', 'title' => 'Your host dashboard', 'body' => 'A quick view of your listings, tenants and money coming in.'],
        ['sel' => '[data-tour="add-property"]', 'title' => 'Add a property', 'body' => 'Create a listing with its photos and location, then add its rooms or units.'],
        ['sel' => '[data-tour="checklist"]', 'title' => 'Your getting-started checklist', 'body' => 'Follow these steps from top to bottom. They tick off by themselves.'],
        ['sel' => '[data-tour="owner-stats"]', 'title' => 'Your numbers', 'body' => 'Units, occupancy, active tenants and pending requests, at a glance.'],
        ['sel' => 'nav a[href$="/owner/properties"]', 'title' => 'My Properties', 'body' => 'Edit listings, units, photos and virtual tours here.'],
        ['sel' => '[data-tour="nav-rentals"]', 'title' => 'Rentals', 'body' => 'Applications, viewing requests, contracts and maintenance requests. A red dot means something is waiting for you.'],
        ['sel' => '[data-tour="nav-menu"]', 'title' => 'Menu', 'body' => 'Tap here to open every page: properties, applications, viewings, contracts and maintenance.'],
        ['sel' => '[data-tour="owner-quick"]', 'title' => 'Quick actions', 'body' => 'Shortcuts to the pages you will use most.'],
        ['sel' => '[data-tour="notifications"]', 'title' => 'Notifications', 'body' => 'New applications, viewing requests and payments are announced here.'],
        ['sel' => '[data-tour="profile"]', 'title' => 'Your account', 'body' => 'Check your verification status, edit your profile, or replay this tour from this menu.'],
        ['sel' => null, 'title' => 'You are ready', 'body' => 'Follow the checklist to get your first listing live. Good luck!'],
    ];

    $obWelcome = $obIsTenant
        ? ['title' => 'Welcome to CaviteRentals, '.$obUser->first_name.'!', 'lead' => 'Find a place to rent in Cavite and handle everything online.', 'points' => ['Search and filter homes, rooms and dorms', 'Book viewings or take a virtual tour', 'Apply, sign your contract and pay online']]
        : ['title' => 'Welcome to CaviteRentals, '.$obUser->first_name.'!', 'lead' => 'List your properties and manage tenants in one place.', 'points' => ['Get verified so you can list properties', 'Add properties and units with photos', 'Handle applications, contracts and payments']];
@endphp

{{-- Welcome popup --}}
<div id="ob-welcome" class="hidden" style="position:fixed;inset:0;z-index:9998;background:rgba(2,6,23,.55);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:16px" role="dialog" aria-modal="true" aria-labelledby="ob-welcome-title">
    <div class="surface-card ob-card p-6 sm:p-8 w-full" style="max-width:460px">
        <svg viewBox="0 0 120 130" width="72" height="78" aria-hidden="true" style="margin:0 auto 12px;display:block">
            @if ($obIsTenant)
                <rect x="82" y="22" width="14" height="28" rx="3" fill="#9f1239"/>
                <path d="M10 64 L60 18 L110 64" fill="#be123c" stroke="#be123c" stroke-width="10" stroke-linejoin="round"/>
                <rect x="20" y="58" width="80" height="64" rx="14" fill="#f43f5e"/>
                <circle cx="46" cy="88" r="6" fill="#fff"/><circle cx="48" cy="86.5" r="3" fill="#0d1117"/>
                <circle cx="74" cy="88" r="6" fill="#fff"/><circle cx="76" cy="86.5" r="3" fill="#0d1117"/>
                <path d="M49 102 Q60 112 71 102" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
            @else
                <rect x="26" y="26" width="68" height="96" rx="14" fill="#3b82f6"/>
                <rect x="20" y="18" width="80" height="16" rx="8" fill="#1d4ed8"/>
                <rect x="38" y="44" width="16" height="14" rx="4" fill="#dbeafe"/><rect x="66" y="44" width="16" height="14" rx="4" fill="#dbeafe"/>
                <rect x="38" y="64" width="16" height="14" rx="4" fill="#dbeafe"/><rect x="66" y="64" width="16" height="14" rx="4" fill="#dbeafe"/>
                <circle cx="48" cy="97" r="6" fill="#fff"/><circle cx="50" cy="95.5" r="3" fill="#0d1117"/>
                <circle cx="72" cy="97" r="6" fill="#fff"/><circle cx="74" cy="95.5" r="3" fill="#0d1117"/>
                <path d="M50 108 Q60 116 70 108" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
            @endif
        </svg>
        <h2 id="ob-welcome-title" class="ob-title text-center" style="font-size:1.4rem;font-weight:800;line-height:1.25">{{ $obWelcome['title'] }}</h2>
        <p class="ob-lead text-center mt-2" style="font-size:.95rem;line-height:1.5">{{ $obWelcome['lead'] }}</p>

        <ul class="mt-5 space-y-2.5">
            @foreach ($obWelcome['points'] as $point)
                <li class="ob-text flex items-start gap-3" style="font-size:.95rem;line-height:1.45">
                    <span style="margin-top:.5rem;width:8px;height:8px;border-radius:9999px;background:#fb7185;flex-shrink:0"></span>{{ $point }}
                </li>
            @endforeach
        </ul>

        <div class="mt-7 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
            <button type="button" id="ob-welcome-skip" class="ob-btn ob-btn-ghost">Skip for now</button>
            <button type="button" id="ob-welcome-start" class="ob-btn ob-btn-primary">Take the 1-minute tour</button>
        </div>
        <p class="ob-faint text-center mt-4" style="font-size:.8rem">You can replay the tour anytime from your profile menu.</p>
    </div>
</div>

{{-- Tour layer --}}
<div id="ob-tour" class="hidden" aria-live="polite">
    <div id="ob-block" style="position:fixed;inset:0;z-index:9996"></div>
    <div id="ob-spot" style="position:fixed;z-index:9997;border-radius:14px;pointer-events:none;box-shadow:0 0 0 9999px rgba(2,6,23,.58),0 0 0 3px #f43f5e;transition:top .25s,left .25s,width .25s,height .25s"></div>
    <div id="ob-tip" class="surface-card ob-card" role="dialog" aria-modal="true" aria-labelledby="ob-tip-title" style="position:fixed;z-index:9999;width:min(340px,calc(100vw - 24px));padding:18px">
        <p id="ob-tip-count" class="ob-faint" style="font-size:.75rem;font-weight:700"></p>
        <p id="ob-tip-title" class="ob-title" style="font-size:1.05rem;font-weight:800;margin-top:4px"></p>
        <p id="ob-tip-body" class="ob-text" style="font-size:.9rem;line-height:1.5;margin-top:6px"></p>
        <div class="flex items-center justify-between gap-3 mt-4">
            <button type="button" id="ob-tip-skip" class="ob-faint ob-link" style="font-size:.8rem;font-weight:600">Skip tour</button>
            <div class="flex gap-2">
                <button type="button" id="ob-tip-back" class="ob-btn ob-btn-ghost ob-btn-sm">Back</button>
                <button type="button" id="ob-tip-next" class="ob-btn ob-btn-primary ob-btn-sm">Next</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var STEPS = @json($obSteps);
    var URLS = {
        welcome: @json(route('onboarding.welcome')),
        done: @json(route('onboarding.tour-completed'))
    };
    var CSRF = @json(csrf_token());
    var SHOW_WELCOME = @json($obShowWelcome);
    var FORCE_TOUR = @json($obForceTour);

    var $ = function (id) { return document.getElementById(id); };
    var welcome = $('ob-welcome'), tour = $('ob-tour'), spot = $('ob-spot'), tip = $('ob-tip');
    var live = [], idx = 0, lastFocus = null;

    function post(url) {
        try { fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, keepalive: true }); } catch (e) {}
    }

    function visible(el) {
        if (!el) return false;
        var r = el.getBoundingClientRect();
        return r.width > 0 && r.height > 0;
    }

    /* ---------- Welcome popup ---------- */
    function openWelcome() {
        lastFocus = document.activeElement;
        welcome.classList.remove('hidden');
        welcome.classList.add('ob-open');
        $('ob-welcome-start').focus();
    }
    function closeWelcome() {
        welcome.classList.remove('ob-open');
        welcome.classList.add('hidden');
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    /* ---------- Tour ---------- */
    function buildSteps() {
        return STEPS.filter(function (s) { return s.sel === null || visible(document.querySelector(s.sel)); });
    }

    function startTour() {
        post(URLS.welcome);
        live = buildSteps();
        if (live.length < 2) { finishTour(false); return; }
        idx = 0;
        tour.classList.remove('hidden');
        showStep();
        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);
        document.addEventListener('keydown', onKey);
    }

    function finishTour(save) {
        tour.classList.add('hidden');
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place, true);
        document.removeEventListener('keydown', onKey);
        if (save !== false) post(URLS.done);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    function showStep() {
        var s = live[idx];
        $('ob-tip-count').textContent = 'Step ' + (idx + 1) + ' of ' + live.length;
        $('ob-tip-title').textContent = s.title;
        $('ob-tip-body').textContent = s.body;
        $('ob-tip-back').style.visibility = idx === 0 ? 'hidden' : 'visible';
        $('ob-tip-next').textContent = idx === live.length - 1 ? 'Done' : 'Next';

        var el = s.sel ? document.querySelector(s.sel) : null;
        if (el) {
            var r = el.getBoundingClientRect();
            var fits = r.top >= 80 && r.bottom <= window.innerHeight - 24;
            if (!fits) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
        place();
        // Re-place once smooth scrolling has settled.
        setTimeout(place, 350);
        $('ob-tip-next').focus({ preventScroll: true });
    }

    function place() {
        if (tour.classList.contains('hidden')) return;
        var s = live[idx];
        var el = s.sel ? document.querySelector(s.sel) : null;
        var vw = window.innerWidth, vh = window.innerHeight, pad = 8;
        var tw = tip.offsetWidth, th = tip.offsetHeight;

        if (!el || !visible(el)) {
            // Centered step: no spotlight hole, just the dimmed page.
            spot.style.top = (vh / 2) + 'px';
            spot.style.left = (vw / 2) + 'px';
            spot.style.width = '0px';
            spot.style.height = '0px';
            tip.style.top = Math.max(12, (vh - th) / 2) + 'px';
            tip.style.left = Math.max(12, (vw - tw) / 2) + 'px';
            return;
        }

        var r = el.getBoundingClientRect();
        // Very tall targets (full sections): highlight the visible part only.
        var top = Math.max(r.top, 8), bottom = Math.min(r.bottom, vh - 8);
        spot.style.top = (top - pad) + 'px';
        spot.style.left = (r.left - pad) + 'px';
        spot.style.width = (r.width + pad * 2) + 'px';
        spot.style.height = (bottom - top + pad * 2) + 'px';

        var below = vh - bottom - pad;
        var above = top - pad;
        var y;
        if (below >= th + 16) y = bottom + pad + 12;
        else if (above >= th + 16) y = top - pad - th - 12;
        else y = Math.max(12, vh - th - 12);   // no room either side: dock at the bottom
        var x = Math.min(Math.max(12, r.left + r.width / 2 - tw / 2), vw - tw - 12);
        tip.style.top = y + 'px';
        tip.style.left = x + 'px';
    }

    function next() { if (idx >= live.length - 1) finishTour(true); else { idx++; showStep(); } }
    function back() { if (idx > 0) { idx--; showStep(); } }
    function onKey(e) {
        if (e.key === 'Escape') finishTour(true);
        else if (e.key === 'ArrowRight' || e.key === 'Enter') { if (document.activeElement.tagName !== 'BUTTON') next(); }
        else if (e.key === 'ArrowLeft') back();
    }

    $('ob-tip-next').addEventListener('click', next);
    $('ob-tip-back').addEventListener('click', back);
    $('ob-tip-skip').addEventListener('click', function () { finishTour(true); });

    $('ob-welcome-start').addEventListener('click', function () { closeWelcome(); startTour(); });
    $('ob-welcome-skip').addEventListener('click', function () { post(URLS.welcome); closeWelcome(); });
    welcome.addEventListener('keydown', function (e) { if (e.key === 'Escape') { post(URLS.welcome); closeWelcome(); } });

    window.addEventListener('load', function () {
        if (FORCE_TOUR) {
            // Drop ?tour=1 so a refresh does not restart it.
            try { history.replaceState(null, '', location.pathname); } catch (e) {}
            setTimeout(startTour, 300);
        } else if (SHOW_WELCOME) {
            setTimeout(openWelcome, 400);
        }
    });
})();
</script>
@endpush
