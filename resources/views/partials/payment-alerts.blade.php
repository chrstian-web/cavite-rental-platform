{{--
    Floating "Payment activity" panel for owners / managers.
    Shows who paid, how much, method and reference. Opens by itself when there
    is something new, collapses to a small pill, and checks for new payments
    every 20 seconds without a page reload.
--}}
@php $paymentAlerts = app(\App\Services\PaymentAlertService::class)->forUser(auth()->user()); @endphp

<script>
    window.paymentAlerts = function (initial) {
        const KEY = 'pa-known';
        return {
            items: initial,
            open: false,
            pulse: false,
            copied: null,
            feedUrl: @json(route('payment-alerts.index')),
            dismissUrl: @json(route('payment-alerts.dismiss', '__ID__')),
            dismissAllUrl: @json(route('payment-alerts.dismiss-all')),
            csrf: @json(csrf_token()),

            init() {
                this.checkForNew();
                setInterval(() => this.refresh(), 20000);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refresh(); });
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

<div x-data="paymentAlerts(@js($paymentAlerts))" x-init="init()" x-show="items.length" x-cloak
     @keydown.escape.window="open = false" class="pa-root">

    {{-- Collapsed pill --}}
    <button type="button" x-show="!open" @click="open = true; pulse = false" class="pa-pill" :class="{ 'pa-pulse': pulse }"
            :aria-label="items.length + ' new payment notifications. Open payment activity.'">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
        </svg>
        <span x-text="items.length + (items.length === 1 ? ' new payment' : ' new payments')"></span>
    </button>

    {{-- Expanded panel --}}
    <section x-show="open" x-transition.opacity.duration.150ms class="pa-panel" role="region" aria-label="Payment activity" aria-live="polite">
        <header class="pa-head">
            <div class="pa-head-title">
                <span>Payment activity</span>
                <span class="pa-count" x-text="items.length"></span>
            </div>
            <div class="pa-head-actions">
                <button type="button" class="pa-link" @click="dismissAll()">Mark all seen</button>
                <button type="button" class="pa-icon-btn" @click="open = false" aria-label="Minimize payment activity">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
        </header>

        <ul class="pa-list">
            <template x-for="item in items" :key="item.id">
                <li class="pa-card">
                    <div class="pa-avatar" :class="'pa-avatar--' + item.kind" x-text="item.initials" aria-hidden="true"></div>
                    <div class="pa-body">
                        <p class="pa-headline" x-text="item.headline"></p>
                        <p class="pa-sub">
                            <span x-text="item.type_label"></span>
                            <template x-if="item.place"><span> · <span x-text="item.place"></span></span></template>
                        </p>

                        <div class="pa-chips">
                            <span class="pa-chip" :class="item.kind === 'review' ? 'pa-chip--warn' : 'pa-chip--ok'" x-text="item.status"></span>
                            <template x-if="item.method"><span class="pa-chip" x-text="item.method"></span></template>
                            <template x-if="item.has_proof"><span class="pa-chip">Screenshot attached</span></template>
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
                                <a :href="item.url" class="pa-cta" x-text="item.kind === 'review' ? 'Review' : 'View'"></a>
                            </span>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
    </section>
</div>
