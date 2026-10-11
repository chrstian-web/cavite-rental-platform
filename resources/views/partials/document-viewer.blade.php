{{--
    One-button document viewer. Shows every submitted file inside the page (no download needed).

    Usage:
      @include('partials.document-viewer', ['items' => \App\Support\DocumentViewerItems::fromApplication($docs)])

    Optional: 'id' (unique per viewer on the page), 'title' (modal heading), 'trigger' => false to hide the
    main button and open it yourself from another button:
      <button type="button" @click="$dispatch('open-docs', { id: 'docs', index: 2 })">View</button>
--}}
@php
    $items = array_values($items ?? []);
    $viewerId = $id ?? 'docs';
    $showTrigger = $trigger ?? true;
    $viewerTitle = $title ?? 'Submitted documents';
@endphp

@once
    <style>
        .dv-trigger { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border-radius: .75rem; background: #2563eb; color: #fff;
            padding: .65rem 1rem; font-size: .875rem; font-weight: 700; line-height: 1.2; transition: background .2s; }
        .dv-trigger:hover { background: #1d4ed8; }
        .dv-trigger svg { width: 1.1rem; height: 1.1rem; flex: none; }

        .dv-root { position: fixed; inset: 0; z-index: 200; display: flex; flex-direction: column; background: rgba(2, 6, 23, .97); color: #e2e8f0;
            height: 100vh; height: 100dvh; font-family: inherit; }
        .dv-bar { display: flex; align-items: center; gap: 10px; padding: calc(10px + env(safe-area-inset-top, 0px)) 12px 10px; border-bottom: 1px solid rgba(148, 163, 184, .2); }
        .dv-title { flex: 1; min-width: 0; }
        .dv-title b { display: block; font-size: .95rem; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dv-title small { display: block; font-size: .72rem; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dv-btn { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 999px;
            background: rgba(148, 163, 184, .16); color: #fff; border: 0; cursor: pointer; transition: background .2s; }
        .dv-btn:hover { background: rgba(148, 163, 184, .3); }
        .dv-btn:disabled { opacity: .35; cursor: default; }
        .dv-btn svg { width: 20px; height: 20px; }
        .dv-count { flex: none; font-size: .75rem; color: #94a3b8; font-variant-numeric: tabular-nums; }

        .dv-stage { position: relative; flex: 1; min-height: 0; overflow: auto; display: flex; align-items: center; justify-content: center; padding: 12px;
            -webkit-overflow-scrolling: touch; touch-action: pan-x pan-y pinch-zoom; }
        .dv-stage.dv-zoomed { align-items: flex-start; justify-content: flex-start; }
        .dv-img { display: block; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 8px; cursor: zoom-in; }
        .dv-zoomed .dv-img { max-width: none; max-height: none; width: 220%; cursor: zoom-out; }
        .dv-frame { width: 100%; height: 100%; border: 0; border-radius: 8px; background: #fff; }
        .dv-msg { text-align: center; max-width: 320px; font-size: .9rem; color: #cbd5e1; line-height: 1.5; }
        .dv-msg a { display: inline-block; margin-top: 12px; padding: .6rem 1rem; border-radius: .75rem; background: #2563eb; color: #fff; font-weight: 700; font-size: .85rem; }
        .dv-load { position: absolute; inset: 0; display: grid; place-items: center; font-size: .85rem; color: #94a3b8; pointer-events: none; }

        .dv-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 2; width: 44px; height: 44px; background: rgba(15, 23, 42, .75); border: 1px solid rgba(148, 163, 184, .3); }
        .dv-nav.prev { left: 8px; } .dv-nav.next { right: 8px; }

        .dv-chips { display: flex; gap: 8px; overflow-x: auto; padding: 10px 12px calc(12px + env(safe-area-inset-bottom, 0px)); border-top: 1px solid rgba(148, 163, 184, .2); scrollbar-width: none; }
        .dv-chips::-webkit-scrollbar { display: none; }
        .dv-chip { flex: none; max-width: 190px; padding: .45rem .8rem; border-radius: 999px; border: 1px solid rgba(148, 163, 184, .3); background: transparent; color: #cbd5e1;
            font-size: .75rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
        .dv-chip[aria-current="true"] { background: #2563eb; border-color: #2563eb; color: #fff; }
        @media (max-width: 480px) { .dv-nav { width: 38px; height: 38px; } }
    </style>

    <script>
        document.addEventListener('alpine:init', function () {
            Alpine.data('docViewer', function (id, items) {
                return {
                    id: id, items: items, open: false, i: 0, zoom: false, loading: true, failed: false, sx: 0, sy: 0,
                    get cur() { return this.items[this.i] || {}; },
                    show: function (n) {
                        this.i = Math.max(0, Math.min(n || 0, this.items.length - 1));
                        this.reset();
                        this.open = true;
                        document.documentElement.style.overflow = 'hidden';
                    },
                    hide: function () { this.open = false; document.documentElement.style.overflow = ''; },
                    reset: function () { this.zoom = false; this.failed = false; this.loading = this.cur.kind === 'image'; },
                    go: function (d) {
                        var n = this.i + d;
                        if (n < 0 || n >= this.items.length) return;
                        this.i = n; this.reset();
                        var el = this.$refs.chips && this.$refs.chips.children[n];
                        if (el && el.scrollIntoView) el.scrollIntoView({ inline: 'center', block: 'nearest' });
                    },
                    touchStart: function (e) { this.sx = e.changedTouches[0].clientX; this.sy = e.changedTouches[0].clientY; },
                    touchEnd: function (e) {
                        if (this.zoom) return; // while zoomed, swiping pans the image
                        var dx = e.changedTouches[0].clientX - this.sx, dy = e.changedTouches[0].clientY - this.sy;
                        if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) this.go(dx < 0 ? 1 : -1);
                    },
                };
            });
        });
    </script>
@endonce

@if (count($items) === 0)
    <p class="text-sm text-slate-400">No documents submitted.</p>
@else
    <div x-data="docViewer({{ \Illuminate\Support\Js::from($viewerId) }}, {{ \Illuminate\Support\Js::from($items) }})"
         @open-docs.window="if ($event.detail && $event.detail.id === id) show($event.detail.index)">

        @if ($showTrigger)
            <button type="button" class="dv-trigger" @click="show(0)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.16-5.16a2.25 2.25 0 013.18 0l5.16 5.16m-1.5-1.5l1.41-1.41a2.25 2.25 0 013.18 0l2.25 2.25M3.75 20.25h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v13.5a1.5 1.5 0 001.5 1.5zM13.5 8.25h.008v.008H13.5V8.25z" /></svg>
                <span>View documents ({{ count($items) }})</span>
            </button>
        @endif

        {{-- Teleported to <body> so no card/transform on the page can clip or hide it --}}
        <template x-teleport="body">
            <div x-show="open" x-cloak style="display:none" class="dv-root" role="dialog" aria-modal="true" aria-label="{{ $viewerTitle }}"
                 @keydown.escape.window="open && hide()"
                 @keydown.arrow-left.window="open && go(-1)"
                 @keydown.arrow-right.window="open && go(1)">

                <div class="dv-bar">
                    <div class="dv-title">
                        <b x-text="cur.label"></b>
                        <small x-text="[cur.name, cur.meta].filter(Boolean).join(' · ')"></small>
                    </div>
                    <span class="dv-count" x-text="(i + 1) + ' / ' + items.length"></span>
                    <a :href="cur.download" class="dv-btn" title="Download" aria-label="Download this file">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12M12 16.5V3" /></svg>
                    </a>
                    <button type="button" class="dv-btn" @click="hide()" aria-label="Close viewer">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="dv-stage" :class="zoom && 'dv-zoomed'" @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
                    <button type="button" class="dv-btn dv-nav prev" x-show="i > 0" @click="go(-1)" aria-label="Previous document">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button type="button" class="dv-btn dv-nav next" x-show="i < items.length - 1" @click="go(1)" aria-label="Next document">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>

                    <template x-if="cur.kind === 'image' && !failed">
                        <img :src="cur.src" :alt="cur.label" class="dv-img" @click="zoom = !zoom" @load="loading = false" x-on:error="failed = true; loading = false">
                    </template>

                    <template x-if="cur.kind === 'pdf' && !failed">
                        <iframe :src="cur.src" class="dv-frame" title="PDF preview"></iframe>
                    </template>

                    <div class="dv-msg" x-show="failed || cur.kind === 'other'">
                        <p>This file can't be previewed here.</p>
                        <a :href="cur.download">Download file</a>
                    </div>

                    <div class="dv-load" x-show="loading && cur.kind === 'image' && !failed">Loading…</div>
                </div>

                <div class="dv-chips" x-ref="chips" x-show="items.length > 1">
                    <template x-for="(it, n) in items" :key="n">
                        <button type="button" class="dv-chip" :aria-current="n === i" @click="i = n; reset()" x-text="it.label"></button>
                    </template>
                </div>
            </div>
        </template>
    </div>
@endif
