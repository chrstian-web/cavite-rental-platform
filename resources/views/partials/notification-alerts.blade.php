{{--
    Floating notification panel for admins, owners/managers and renters.
    Shows every new notification (viewing requests, verifications, payments with
    full details, application/viewing/contract updates...). Opens by itself when
    there is something new, collapses to a small pill, and checks for new items
    every 20 seconds without a page reload.
--}}
@php $notificationAlerts = app(\App\Services\NotificationAlertService::class)->forUser(auth()->user()); @endphp

<script>
    window.notificationAlerts = function (initial) {
        const KEY = 'na-known';
        return {
            items: initial,
            open: false,
            pulse: false,
            copied: null,
            feedUrl: @json(route('alerts.index')),
            dismissUrl: @json(route('alerts.dismiss', '__ID__')),
            dismissAllUrl: @json(route('alerts.dismiss-all')),
            csrf: @json(csrf_token()),

            isMobile() {
                return window.matchMedia('(max-width: 640px)').matches;
            },
            init() {
                this.checkForNew();

                // Phones: pop up once each time the app/site is opened, even if the items were seen before.
                try {
                    if (this.isMobile() && this.items.length && !sessionStorage.getItem('na-launched')) {
                        this.open = true; this.pulse = true;
                    }
                    sessionStorage.setItem('na-launched', '1');
                } catch (e) {}

                // Lock page scroll behind the pop-up on phones.
                this.$watch('open', (v) => { document.body.style.overflow = (v && this.isMobile()) ? 'hidden' : ''; });
                if (this.open && this.isMobile()) document.body.style.overflow = 'hidden';

                setInterval(() => this.refresh(), 20000);

                // Coming back to the app after a while (phone unlocked / tab switched back): pop up again.
                let hiddenAt = null;
                document.addEventListener('visibilitychange', async () => {
                    if (document.hidden) { hiddenAt = Date.now(); return; }
                    const away = hiddenAt ? Date.now() - hiddenAt : 0;
                    hiddenAt = null;
                    await this.refresh();
                    if (this.isMobile() && away > 30000 && this.items.length) { this.open = true; this.pulse = true; }
                });
            },
            known() {
                try { return new Set(JSON.parse(sessionStorage.getItem(KEY) || '[]')); } catch (e) { return new Set(); }
            },
            remember() {
                try {
                    const all = this.known();
                    this.items.forEach(i => all.add(i.id));
                    sessionStorage.setItem(KEY, JSON.stringify([...all].slice(-200)));
                } catch (e) {}
            },
            // Open (and pulse) when there is an alert this browser session has not shown yet.
            checkForNew() {
                const known = this.known();
                if (this.items.some(i => !known.has(i.id))) { this.open = true; this.pulse = true; }
                this.remember();
            },
            async refresh() {
                try {
                    const res = await fetch(this.feedUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    if (!res.ok) return;
                    this.items = (await res.json()).alerts || [];
                    this.checkForNew();
                    if (!this.items.length) this.open = false;
                } catch (e) {}
            },
            async post(url) {
                try { await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' }, credentials: 'same-origin', keepalive: true }); } catch (e) {}
            },
            dismiss(item) {
                this.items = this.items.filter(i => i.id !== item.id);
                if (!this.items.length) this.open = false;
                this.post(this.dismissUrl.replace('__ID__', item.id));
            },
            dismissAll() {
                this.items = [];
                this.open = false;
                this.post(this.dismissAllUrl);
            },
            copy(item) {
                if (!item.reference || !navigator.clipboard) return;
                navigator.clipboard.writeText(item.reference).then(() => {
                    this.copied = item.id;
                    setTimeout(() => { if (this.copied === item.id) this.copied = null; }, 1500);
                });
            },
        };
    };
</script>

<div x-data="notificationAlerts(@js($notificationAlerts))" x-init="init()" x-show="items.length" x-cloak
     @keydown.escape.window="open = false" class="pa-root" :class="{ 'pa-open': open }">

    {{-- Dimmed backdrop (phones only): tap outside the pop-up to close it --}}
    <div class="pa-backdrop" x-show="open" x-transition.opacity @click="open = false" aria-hidden="true"></div>

    {{-- Collapsed pill --}}
    <button type="button" x-show="!open" @click="open = true; pulse = false" class="pa-pill" :class="{ 'pa-pulse': pulse }"
            :aria-label="items.length + ' new notifications. Open notifications.'">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />
        </svg>
        <span x-text="items.length + (items.length === 1 ? ' new notification' : ' new notifications')"></span>
    </button>

    {{-- Expanded panel --}}
    <section x-show="open" x-transition.opacity.duration.150ms class="pa-panel" role="region" aria-label="Notifications" aria-live="polite">
        <header class="pa-head">
            <div class="pa-head-title">
                <span>Notifications</span>
                <span class="pa-count" x-text="items.length"></span>
            </div>
            <div class="pa-head-actions">
                <button type="button" class="pa-link" @click="dismissAll()">Mark all seen</button>
                <button type="button" class="pa-icon-btn" @click="open = false" aria-label="Minimize notifications">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
        </header>

        <ul class="pa-list">
            <template x-for="item in items" :key="item.id">
                <li class="pa-card">
                    <div class="pa-avatar" :class="'pa-avatar--' + item.kind" x-text="item.initials" aria-hidden="true"></div>
                    <div class="pa-body">
                        <p class="pa-cat" x-text="item.category"></p>
                        <p class="pa-headline" x-text="item.headline"></p>
                        <template x-if="item.sub"><p class="pa-sub" x-text="item.sub"></p></template>

                        <template x-if="item.note"><p class="pa-note" x-text="item.note"></p></template>

                        <div class="pa-chips">
                            <template x-for="(chip, i) in item.chips" :key="i">
                                <span class="pa-chip" :class="chip.tone && chip.tone !== 'neutral' ? 'pa-chip--' + chip.tone : ''" x-text="chip.text"></span>
                            </template>
                        </div>

                        <template x-if="item.reference">
                            <button type="button" class="pa-ref" @click="copy(item)" title="Copy reference number">
                                <span class="pa-ref-label">Ref</span>
                                <span class="pa-ref-value" x-text="item.reference"></span>
                                <span class="pa-ref-copy" x-text="copied === item.id ? 'Copied' : 'Copy'"></span>
                            </button>
                        </template>

                        <div class="pa-foot">
                            <span class="pa-when" x-text="item.when"></span>
                            <span class="pa-foot-actions">
                                <button type="button" class="pa-link" @click="dismiss(item)">Mark seen</button>
                                <a :href="item.url" class="pa-cta" @click="dismiss(item)" x-text="item.cta"></a>
                            </span>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
    </section>
</div>
