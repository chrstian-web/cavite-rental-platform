{{--
    Reusable responsive 360-degree virtual-tour viewer.
    Required: $tour (VirtualTour, with scenes.hotspots eager loaded), $viewerId (unique string per page)
--}}
@php
    $scenesJson = $tour->scenes->mapWithKeys(fn ($scene) => [
        'scene-'.$scene->id => [
            'title' => $scene->title,
            'description' => $scene->description,
            'type' => 'equirectangular',
            // Images are served through an authorization-aware endpoint rather than
            // exposing the public storage path in the page source.
            'panorama' => route('virtual-tour.panorama', [$tour->property_id, $tour->id, $scene->id]),
            'thumbnail' => route('virtual-tour.panorama', [$tour->property_id, $tour->id, $scene->id]),
            'hotSpots' => $scene->hotspots->map(function ($hotspot) use ($tour) {
                $target = $hotspot->target_scene_id
                    ? $tour->scenes->firstWhere('id', $hotspot->target_scene_id)
                    : null;

                $size = max(24, min(140, (int) ($hotspot->size ?: 56)));

                return [
                'id' => 'hotspot-'.$hotspot->id,
                'pitch' => (float) $hotspot->position_y,
                'yaw' => (float) $hotspot->position_x,
                'type' => $hotspot->target_scene_id ? 'scene' : 'info',
                'text' => $hotspot->label ?: ($target ? 'Go to '.$target->title : 'Information'),
                'sceneId' => $hotspot->target_scene_id ? 'scene-'.$hotspot->target_scene_id : null,
                'cssClass' => $hotspot->target_scene_id ? 'tour-hotspot-arrow tour-size-'.$size : 'tour-hotspot-info',
                ];
            })->values()->toArray(),
        ],
    ])->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $firstSceneId = 'scene-'.$tour->scenes->first()->id;
    $arrowSizes = $tour->scenes->flatMap(fn ($scene) => $scene->hotspots)
        ->map(fn ($hotspot) => max(24, min(140, (int) ($hotspot->size ?: 56))))
        ->push(56)->unique()->values();
@endphp

<style>
    /* Per-arrow sizes chosen by the owner (one tiny rule per distinct size). */
@foreach ($arrowSizes as $px)
    .tour-size-{{ $px }} { --arrow-size: {{ $px }}px; }
@endforeach

    /*
     * Navigation arrow (renters + owner placement marker).
     * NOTE: never set `transform` on the hotspot element itself — Pannellum uses it
     * for positioning. All animation/scaling happens on the pseudo-elements.
     */
    .tour-hotspot-arrow,
    .tour-placement-marker {
        position: absolute !important;
        display: block !important;
        width: var(--arrow-size, 56px) !important;
        height: var(--arrow-size, 56px) !important;
        box-sizing: border-box !important;
        border-radius: 9999px !important;
        border: 2px solid rgba(255, 255, 255, .95) !important;
        background:
            radial-gradient(circle at 32% 22%, #ffffff 0%, #eef2f7 45%, #cbd5e1 100%) !important;
        box-shadow:
            0 8px 20px rgba(2, 6, 23, .5),
            inset 0 -4px 8px rgba(15, 23, 42, .16),
            inset 0 2px 3px rgba(255, 255, 255, .9);
        cursor: pointer;
        transition: box-shadow .18s ease, filter .18s ease;
    }

    /* Upward double chevron (points forward / "go there") */
    .tour-hotspot-arrow::after,
    .tour-placement-marker::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230f172a' stroke-width='2.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 11l6-6 6 6' opacity='.55'/%3E%3Cpath d='M6 18l6-6 6 6'/%3E%3C/svg%3E") center / 62% no-repeat;
        filter: drop-shadow(0 1px 0 rgba(255, 255, 255, .8));
    }

    /* Pulsing ring */
    .tour-hotspot-arrow::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 9999px;
        border: 2px solid rgba(255, 255, 255, .85);
        pointer-events: none;
        animation: tour-ring 1.9s ease-out infinite;
    }

    .tour-hotspot-arrow:hover,
    .tour-hotspot-arrow:focus {
        filter: brightness(1.05);
        box-shadow:
            0 0 0 6px rgba(255, 255, 255, .35),
            0 10px 24px rgba(2, 6, 23, .55),
            inset 0 -4px 8px rgba(15, 23, 42, .16),
            inset 0 2px 3px rgba(255, 255, 255, .9);
        outline: none;
    }

    /* Owner editor marker: same arrow, blue accent so it's not mistaken for a saved one */
    .tour-placement-marker {
        cursor: grab;
        z-index: 20;
        border-color: #2563eb !important;
        box-shadow:
            0 8px 22px rgba(2, 6, 23, .55),
            0 0 0 5px rgba(37, 99, 235, .25),
            inset 0 -4px 8px rgba(15, 23, 42, .16),
            inset 0 2px 3px rgba(255, 255, 255, .9);
    }

    .tour-placement-marker:active {
        cursor: grabbing;
    }

    @keyframes tour-ring {
        0%   { transform: scale(1);   opacity: .9; }
        100% { transform: scale(1.7); opacity: 0; }
    }

    .tour-hotspot-info {
        position: absolute !important;
        width: 28px;
        height: 28px;
        background: #0f172a;
        border: 2px solid white;
        border-radius: 9999px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .35);
        transition: box-shadow .15s ease;
    }

    .tour-hotspot-info::after {
        content: 'i';
        color: white;
        display: grid;
        place-items: center;
        height: 100%;
        font: 700 16px/1 sans-serif;
    }

    .tour-hotspot-info:hover,
    .tour-hotspot-info:focus {
        box-shadow:
            0 0 0 4px rgba(255, 255, 255, .45),
            0 2px 8px rgba(0, 0, 0, .4);
        outline: none;
    }

    .tour-viewer-overlay {
        overscroll-behavior: contain;
    }

    .tour-viewer-overlay .pnlm-container {
        background: #020617;
    }

    .tour-viewer-thumbnails {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .55) transparent;
    }

    .tour-viewer-thumbnail[aria-current="true"] {
        border-color: white;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, .35);
    }

    /*
     * Fix the oversized previous and close buttons inside
     * the virtual-tour fullscreen overlay.
     */
    .tour-viewer-overlay button > svg {
        display: block;
        flex: 0 0 auto;
        width: 20px !important;
        height: 20px !important;
        max-width: 20px !important;
        max-height: 20px !important;
    }

    /*
     * Keep the panorama visible even if Tailwind's
     * arbitrary-height utility is unavailable or overridden.
     */
    .tour-inline-viewer {
        display: block;
        width: 100%;
        height: 320px !important;
        min-height: 320px;
        background: #020617;
    }

    @media (min-width: 640px) {
        .tour-inline-viewer {
            height: 420px !important;
            min-height: 420px;
        }
    }
</style>


<div class="relative">
    <div id="{{ $viewerId }}-inline" class="tour-inline-viewer w-full rounded-xl overflow-hidden bg-slate-950" aria-label="360 degree virtual tour preview"></div>
    <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap gap-2" aria-label="Tour scenes">
            @foreach ($tour->scenes as $scene)
                <button type="button" onclick="window['{{ $viewerId }}_goto']('scene-{{ $scene->id }}')"
                    class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full px-3 py-1.5">
                    {{ $scene->title }}
                </button>
            @endforeach
        </div>
        <button type="button" onclick="window['{{ $viewerId }}_openFullscreen']()"
            class="text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-full px-4 py-2 whitespace-nowrap">
            View Fullscreen Tour
        </button>
    </div>
</div>

<div id="{{ $viewerId }}-overlay" class="tour-viewer-overlay fixed inset-0 bg-slate-950 z-[60] hidden" role="dialog" aria-modal="true" aria-labelledby="{{ $viewerId }}-title">
    <div class="absolute inset-0" id="{{ $viewerId }}-full" aria-label="Interactive 360 degree virtual tour"></div>

    <div class="absolute top-0 inset-x-0 z-20 p-3 sm:p-5 bg-gradient-to-b from-black/70 to-transparent pointer-events-none">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="pointer-events-auto min-w-0">
                <p id="{{ $viewerId }}-title" class="text-white font-semibold truncate"></p>
                <p id="{{ $viewerId }}-description" class="text-white/70 text-xs mt-1 max-w-xl truncate"></p>
            </div>
            <div class="pointer-events-auto flex items-center gap-2 shrink-0">
                <div class="flex rounded-full bg-black/55 border border-white/20 p-1" role="group" aria-label="Tour viewing mode">
                    <button type="button" id="{{ $viewerId }}-photo-mode" class="rounded-full px-3 py-1.5 text-xs font-medium text-white bg-white/20" aria-pressed="true">
                        360 Photo
                    </button>
                    <button type="button" id="{{ $viewerId }}-animate-mode" class="rounded-full px-3 py-1.5 text-xs font-medium text-white/70 hover:text-white" aria-pressed="false">
                        Animate
                    </button>
                </div>
                <button type="button" onclick="window['{{ $viewerId }}_closeFullscreen']()"
                    class="rounded-full bg-black/55 border border-white/20 px-3 py-2 text-white text-sm hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-white"
                    aria-label="Close virtual tour">
                    <span aria-hidden="true">✕</span><span class="hidden sm:inline ml-1">Close</span>
                </button>
            </div>
        </div>
    </div>

    <div id="{{ $viewerId }}-loading" class="absolute inset-0 z-10 grid place-items-center pointer-events-none" role="status" aria-live="polite">
        <div class="rounded-xl bg-black/65 px-4 py-3 text-sm text-white">Loading 360° scene…</div>
    </div>
    <div id="{{ $viewerId }}-error" class="absolute inset-0 z-30 hidden place-items-center p-6" role="alert">
        <div class="max-w-sm rounded-xl bg-black/80 p-5 text-center text-white">
            <p class="font-semibold">This scene could not be loaded.</p>
            <p class="mt-1 text-sm text-white/70">Please try another scene or refresh the page.</p>
            <button type="button" id="{{ $viewerId }}-retry" class="mt-4 rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100">Try again</button>
        </div>
    </div>

    <button type="button" id="{{ $viewerId }}-previous" class="absolute left-3 sm:left-5 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/55 border border-white/20 p-3 text-white hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-white" aria-label="Go to previous scene">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 15.79a.75.75 0 01-1.06 0l-5-5a.75.75 0 010-1.06l5-5a.75.75 0 111.06 1.06L8.32 10.25l4.47 4.48a.75.75 0 010 1.06z" clip-rule="evenodd" /></svg>
    </button>

    <div class="absolute bottom-0 inset-x-0 z-20 p-3 sm:p-5 bg-gradient-to-t from-black/80 via-black/45 to-transparent">
        <div class="flex items-end gap-3">
            <div id="{{ $viewerId }}-thumbnails" class="tour-viewer-thumbnails flex gap-2 overflow-x-auto pb-1 min-w-0" role="list" aria-label="Tour scenes"></div>
            <button type="button" onclick="window['{{ $viewerId }}_closeFullscreen']()" class="shrink-0 rounded-full bg-black/55 border border-white/20 p-3 text-white hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-white" aria-label="Exit virtual tour">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const scenes = {!! $scenesJson !!};
    const firstScene = '{{ $firstSceneId }}';
    const ids = {
        inline: '{{ $viewerId }}-inline', overlay: '{{ $viewerId }}-overlay', full: '{{ $viewerId }}-full',
        title: '{{ $viewerId }}-title', description: '{{ $viewerId }}-description', loading: '{{ $viewerId }}-loading',
        error: '{{ $viewerId }}-error', retry: '{{ $viewerId }}-retry', previous: '{{ $viewerId }}-previous',
        thumbnails: '{{ $viewerId }}-thumbnails', photoMode: '{{ $viewerId }}-photo-mode', animateMode: '{{ $viewerId }}-animate-mode'
    };
    let inlineViewer = null;
    let fullViewer = null;
    let currentScene = firstScene;
    let sceneHistory = [];
    let lastFocusedElement = null;

    function el(key) { return document.getElementById(ids[key]); }
    function setLoading(show) { el('loading').classList.toggle('hidden', !show); }
    function setError(show) { el('error').classList.toggle('grid', show); el('error').classList.toggle('hidden', !show); }
    function setMode(animate) {
        el('photoMode').classList.toggle('bg-white/20', !animate);
        el('animateMode').classList.toggle('bg-white/20', animate);
        el('photoMode').classList.toggle('text-white/70', animate);
        el('animateMode').classList.toggle('text-white/70', !animate);
        el('photoMode').setAttribute('aria-pressed', String(!animate));
        el('animateMode').setAttribute('aria-pressed', String(animate));
        if (!fullViewer) return;
        if (animate) fullViewer.startAutoRotate(-2); else fullViewer.stopAutoRotate();
    }
    function updateSceneChrome(sceneId) {
        currentScene = sceneId;
        const scene = scenes[sceneId];
        if (!scene) return;
        el('title').textContent = scene.title || '';
        el('description').textContent = scene.description || '';
        document.querySelectorAll('#' + ids.thumbnails + ' button').forEach(button => {
            button.setAttribute('aria-current', button.dataset.scene === sceneId ? 'true' : 'false');
        });
    }
    function enhanceHotspots() {
        const root = document.getElementById(ids.full);
        if (!root) return;
        root.querySelectorAll('.pnlm-hotspot').forEach(hotspot => {
            hotspot.setAttribute('role', 'button');
            hotspot.setAttribute('tabindex', '0');
            hotspot.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    hotspot.click();
                }
            }, { once: true });
        });
    }
    function bindViewerEvents(viewer) {
        viewer.on('scenechange', function (sceneId) {
            updateSceneChrome(sceneId);
            setLoading(true);
            setError(false);
            window.setTimeout(function () { setLoading(false); enhanceHotspots(); }, 250);
        });
        viewer.on('load', function () {
            setLoading(false);
            setError(false);
            enhanceHotspots();
        });
        viewer.on('error', function () {
            setLoading(false);
            setError(true);
        });
    }
    function baseConfig() {
        return {
            default: {
                firstScene: firstScene,
                sceneFadeDuration: 700,
                autoLoad: true,
                showZoomCtrl: true,
                showFullscreenCtrl: false,
                keyboardZoom: true,
                mouseZoom: true,
                compass: false,
                hotSpotDebug: false,
            },
            scenes: JSON.parse(JSON.stringify(scenes)),
        };
    }
    function renderThumbnails() {
        const container = el('thumbnails');
        Object.entries(scenes).forEach(([sceneId, scene]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.scene = sceneId;
            button.className = 'tour-viewer-thumbnail shrink-0 w-28 sm:w-36 rounded-lg border-2 border-white/20 bg-black/60 px-3 py-2 text-left text-white hover:border-white/70 focus:outline-none focus:ring-2 focus:ring-white';
            button.setAttribute('aria-current', sceneId === firstScene ? 'true' : 'false');
            button.setAttribute('aria-label', 'Open scene: ' + scene.title);
            button.innerHTML = '<span class="block h-12 sm:h-16 mb-1 rounded bg-cover bg-center bg-slate-700/80" style="background-image:url(\'' + escapeHtml(scene.thumbnail) + '\')" aria-hidden="true"></span><span class="block truncate text-xs font-medium">' + escapeHtml(scene.title || 'Scene') + '</span>';
            button.addEventListener('click', function () { loadFullScene(sceneId, true); });
            container.appendChild(button);
        });
    }
    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, function (character) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character];
        });
    }
    function loadFullScene(sceneId, addHistory) {
        if (!fullViewer || !scenes[sceneId]) return;
        if (addHistory && currentScene !== sceneId) sceneHistory.push(currentScene);
        setLoading(true);
        setError(false);
        fullViewer.loadScene(sceneId);
    }
    window['{{ $viewerId }}_getInlineViewer'] = function () { return inlineViewer; };
    document.addEventListener('DOMContentLoaded', function () {
        renderThumbnails();
        if (typeof window.pannellum === 'undefined') {
            document.getElementById(ids.inline).innerHTML = '<div class="h-full grid place-items-center p-6 text-center text-sm text-white/70">The 360° viewer could not start. Please refresh the page.</div>';
            return;
        }
        inlineViewer = pannellum.viewer(ids.inline, baseConfig());
        inlineViewer.on('error', function () {
            document.getElementById(ids.inline).innerHTML = '<div class="h-full grid place-items-center p-6 text-center text-sm text-white/70">The 360° preview could not be loaded.</div>';
        });
        el('photoMode').addEventListener('click', function () { setMode(false); });
        el('animateMode').addEventListener('click', function () { setMode(true); });
        el('retry').addEventListener('click', function () { loadFullScene(currentScene, false); });
        el('previous').addEventListener('click', function () {
            const previous = sceneHistory.pop();
            if (previous) loadFullScene(previous, false);
        });
    });
    window['{{ $viewerId }}_goto'] = function (sceneId) {
        if (inlineViewer && scenes[sceneId]) {
            currentScene = sceneId;
            inlineViewer.loadScene(sceneId);
        }
    };
    window['{{ $viewerId }}_openFullscreen'] = function () {
        lastFocusedElement = document.activeElement;
        const overlay = el('overlay');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        sceneHistory = [];
        if (!fullViewer) {
            fullViewer = pannellum.viewer(ids.full, baseConfig());
            bindViewerEvents(fullViewer);
        } else {
            fullViewer.resize();
            loadFullScene(currentScene, false);
        }
        updateSceneChrome(currentScene);
        window.setTimeout(function () { el('photoMode').focus(); }, 50);
    };
    window['{{ $viewerId }}_closeFullscreen'] = function () {
        el('overlay').classList.add('hidden');
        document.body.style.overflow = '';
        setMode(false);
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') lastFocusedElement.focus();
    };
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !el('overlay').classList.contains('hidden')) window['{{ $viewerId }}_closeFullscreen']();
    });
})();
</script>
