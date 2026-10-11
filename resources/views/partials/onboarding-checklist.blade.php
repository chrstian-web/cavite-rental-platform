{{-- "Getting started" checklist. Owners: ticks from real data. Renters: only the phone number is required; the other steps tick when their tutorial is opened. --}}
@php
    $obUser = auth()->user();
    $checklist = $obUser->onboarding_checklist_dismissed_at ? null : app(\App\Services\OnboardingChecklist::class)->for($obUser);
    $pct = $checklist ? (int) round($checklist['done'] / max(1, $checklist['total']) * 100) : 0;
    $guides = [];
    if ($checklist) {
        foreach ($checklist['items'] as $it) {
            $guides[$it['key']] = ['title' => $it['title'], 'url' => $it['url']] + $it['guide'];
        }
    }
@endphp

@if ($checklist && ($checklist['complete'] || $obUser->isTenant()))
    {{-- Appreciation card. Shown instead of the checklist once every step is done. Renters get it pre-rendered but hidden,
         so it can appear the moment they tick their last step. --}}
    @php
        $firstName = $obUser->first_name;
        $isRenter = $obUser->isTenant();
    @endphp
    <div id="ob-thanks" class="surface-card ob-thanks ob-thanks-in p-6 sm:p-8 mb-8" role="status" @unless ($checklist['complete']) style="display:none" @endunless>
        <div class="ob-confetti" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>

        <div class="ob-thanks-badge" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        </div>

        <p class="text-xl font-extrabold text-slate-950">
            {{ $isRenter ? 'You are all set' : 'Congratulations' }}{{ filled($firstName) ? ', '.$firstName : '' }}!
        </p>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
            @if ($isRenter)
                Thank you for completing your getting-started guide. You now know how to save homes, book viewings, apply for a rental and sign your contract. We are glad to have you on Cavite Rentals.
            @else
                Thank you for setting up your listing. You are verified and your property is approved, so renters can now find it. We are glad to have you on Cavite Rentals.
            @endif
        </p>

        <div class="ob-thanks-actions">
            @if ($isRenter)
                <a href="{{ route('properties.index') }}" class="ob-btn ob-btn-primary">Browse homes</a>
            @else
                <a href="{{ route('owner.properties.index') }}" class="ob-btn ob-btn-primary">Go to my properties</a>
            @endif
            <button type="button" id="ob-thanks-close" class="ob-btn ob-btn-ghost">Close</button>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var btn = document.getElementById('ob-thanks-close');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    document.getElementById('ob-thanks').remove();
                    fetch(@json(route('onboarding.checklist.dismiss')), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' },
                        keepalive: true
                    });
                });
            })();
        </script>
    @endpush
@endif

@if ($checklist && ! $checklist['complete'])
    <div id="ob-checklist" data-tour="checklist" class="surface-card p-5 mb-8">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-base font-extrabold text-slate-950">Getting started</p>
                <p class="text-xs text-slate-500 mt-0.5"><span id="ob-count">{{ $checklist['done'] }} of {{ $checklist['total'] }} done.</span>
                    @if ($obUser->isTenant())
                        Only your phone number is required. Open a step to see how it works and it ticks off.
                    @else
                        These tick off by themselves as you go.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <button type="button" id="ob-what-next" data-guide-key="{{ $checklist['next'] }}" class="text-xs font-bold text-rose-500 hover:underline">What should I do?</button>
                <button type="button" id="ob-checklist-dismiss" class="text-xs font-semibold text-slate-400 hover:text-slate-600" aria-label="Hide the getting started checklist">Hide</button>
            </div>
        </div>

        <div id="ob-progress" class="h-1.5 bg-slate-100 rounded-full overflow-hidden mt-3" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Getting started progress">
            <div id="ob-bar" class="h-full bg-rose-500 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
        </div>

        <ul class="mt-4 divide-y divide-slate-100">
            @foreach ($checklist['items'] as $item)
                @php
                    $isNext = $checklist['next'] === $item['key'] && empty($item['locked']);
                    $locked = ! empty($item['locked']) && ! $item['done'];
                @endphp
                <li class="flex items-start gap-3 py-3" data-ob-item="{{ $item['key'] }}" data-done="{{ $item['done'] ? 1 : 0 }}" data-locked="{{ $locked ? 1 : 0 }}" data-markable="{{ ! empty($item['markable']) ? 1 : 0 }}">
                    @if ($item['done'])
                        <span data-ob-icon class="mt-0.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0" aria-label="Done">
                            <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @else
                        <span data-ob-icon class="mt-0.5 w-5 h-5 rounded-full border-2 {{ $isNext ? 'border-rose-500' : 'border-slate-300' }} shrink-0" aria-label="Not done yet"></span>
                    @endif

                    <div class="min-w-0 flex-1">
                        @if ($item['done'])
                            <p class="text-sm font-bold text-slate-400 line-through">{{ $item['title'] }}</p>
                        @else
                            <button type="button" data-ob-title data-guide-key="{{ $item['key'] }}" class="text-sm font-bold text-left {{ $locked ? 'text-slate-500' : 'text-slate-900' }} hover:underline">{{ $item['title'] }}</button>
                        @endif
                        @unless ($item['done'])
                            <p data-ob-hint class="text-xs text-slate-500 mt-0.5">{{ $item['hint'] }}</p>
                        @endunless
                    </div>

                    @if (! $item['done'])
                        <button type="button" data-ob-action data-guide-key="{{ $item['key'] }}" class="text-xs font-bold shrink-0 {{ $isNext ? 'text-rose-600 hover:underline' : 'text-slate-500 hover:underline' }}">
                            {{ $locked ? 'See why' : ($isNext ? 'Do this next' : 'See how') }}
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <script type="application/json" id="ob-guides">@json($guides, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)</script>

    @push('scripts')
        <script>
            (function () {
                var guides = {};
                try { guides = JSON.parse(document.getElementById('ob-guides').textContent); } catch (e) {}

                var list = document.querySelector('#ob-checklist ul');
                var SEEN_URL = @json(route('onboarding.checklist.seen'));
                var CSRF = @json(csrf_token());
                var CHECK = '<span data-ob-icon class="mt-0.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0" aria-label="Done">'
                    + '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg></span>';

                function rowFor(key) { return list.querySelector('li[data-ob-item="' + key + '"]'); }

                // Recount the ticks, move the progress bar and move the "Do this next" marker.
                function refresh() {
                    var rows = list.querySelectorAll('li[data-ob-item]');
                    var done = list.querySelectorAll('li[data-done="1"]').length;
                    var pct = Math.round(done / Math.max(1, rows.length) * 100);
                    document.getElementById('ob-count').textContent = done + ' of ' + rows.length + ' done.';
                    document.getElementById('ob-bar').style.width = pct + '%';
                    document.getElementById('ob-progress').setAttribute('aria-valuenow', pct);

                    var next = null;
                    rows.forEach(function (li) {
                        if (!next && li.getAttribute('data-done') !== '1' && li.getAttribute('data-locked') !== '1') next = li;
                    });
                    rows.forEach(function (li) {
                        if (li.getAttribute('data-done') === '1') return;
                        var isNext = li === next, locked = li.getAttribute('data-locked') === '1';
                        var icon = li.querySelector('[data-ob-icon]');
                        icon.classList.remove('border-rose-500', 'border-slate-300');
                        icon.classList.add(isNext ? 'border-rose-500' : 'border-slate-300');
                        var act = li.querySelector('[data-ob-action]');
                        act.textContent = locked ? 'See why' : (isNext ? 'Do this next' : 'See how');
                        act.classList.remove('text-rose-600', 'text-slate-500');
                        act.classList.add(isNext ? 'text-rose-600' : 'text-slate-500');
                    });
                    var what = document.getElementById('ob-what-next');
                    if (what) {
                        if (next) what.setAttribute('data-guide-key', next.getAttribute('data-ob-item'));
                        else what.remove();
                    }
                    if (done === rows.length) showThanks();
                }

                // Last step ticked: swap the checklist for the appreciation card. If the tutorial popup is
                // still open, wait until it is closed so the renter actually sees the card.
                function showThanks() {
                    var thanks = document.getElementById('ob-thanks');
                    if (!thanks) return;
                    var swap = function () {
                        document.getElementById('ob-checklist').style.display = 'none';
                        thanks.style.display = '';
                    };
                    var modal = document.getElementById('ob-guide');
                    if (modal && modal.classList.contains('ob-open')) {
                        var mo = new MutationObserver(function () {
                            if (!modal.classList.contains('ob-open')) { mo.disconnect(); swap(); }
                        });
                        mo.observe(modal, { attributes: true, attributeFilter: ['class'] });
                    } else {
                        swap();
                    }
                }

                function tick(li) {
                    li.setAttribute('data-done', '1');
                    li.querySelector('[data-ob-icon]').outerHTML = CHECK;
                    var title = li.querySelector('[data-ob-title]');
                    if (title) {
                        var p = document.createElement('p');
                        p.className = 'text-sm font-bold text-slate-400 line-through';
                        p.textContent = title.textContent;
                        title.replaceWith(p);
                    }
                    ['[data-ob-hint]', '[data-ob-action]'].forEach(function (sel) {
                        var el = li.querySelector(sel);
                        if (el) el.remove();
                    });
                    refresh();
                }

                // Opening a renter step's tutorial ticks it. Saved on the account, so it stays ticked.
                function markSeen(key) {
                    var li = rowFor(key);
                    if (!li || li.getAttribute('data-markable') !== '1' || li.getAttribute('data-done') === '1') return;
                    fetch(SEEN_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ key: key }),
                        keepalive: true
                    }).then(function (r) { if (r.ok) tick(li); }).catch(function () {});
                }

                document.querySelectorAll('#ob-checklist [data-guide-key]').forEach(function (el) {
                    el.addEventListener('click', function () {
                        var key = el.getAttribute('data-guide-key');
                        var g = guides[key];
                        if (g && window.obOpenGuide) window.obOpenGuide(g, el);
                        markSeen(key);
                    });
                });

                var btn = document.getElementById('ob-checklist-dismiss');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    document.getElementById('ob-checklist').remove();
                    fetch(@json(route('onboarding.checklist.dismiss')), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' },
                        keepalive: true
                    });
                });
            })();
        </script>
    @endpush
@endif
