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
            'hotSpots' => $scene->hotspots->map(fn ($hotspot) => [
                'pitch' => (float) $hotspot->position_y,
                'yaw' => (float) $hotspot->position_x,
                'type' => $hotspot->target_scene_id ? 'scene' : 'info',
                'text' => $hotspot->label ?: ($hotspot->target_scene_id ? 'Go to '.$scene->title : 'Information'),
                'sceneId' => $hotspot->target_scene_id ? 'scene-'.$hotspot->target_scene_id : null,
                'cssClass' => $hotspot->target_scene_id ? 'tour-hotspot-arrow' : 'tour-hotspot-info',
            ])->toArray(),
        ],
    ])->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $firstSceneId = 'scene-'.$tour->scenes->first()->id;
@endphp

<style>
    .tour-hotspot-arrow,
    .tour-hotspot-info {
        position: relative;
        border: 2px solid white;
        border-radius: 9999px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .35);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .tour-hotspot-arrow {
        width: 34px;
        height: 34px;
        background: #2563eb;
        animation: tour-pulse 1.8s ease-in-out infinite;
    }
    .tour-hotspot-arrow::after {
        content: '';
        position: absolute;
        inset: 0;
        width: 10px;
        height: 10px;
        margin: auto;
        border-top: 3px solid white;
        border-right: 3px solid white;
        transform: rotate(45deg);
    }
    .tour-hotspot-info {
        width: 28px;
        height: 28px;
        background: #0f172a;
    }
    .tour-hotspot-info::after {
        content: 'i';
        color: white;
        display: grid;
        place-items: center;
        height: 100%;
        font: 700 16px/1 sans-serif;
    }
    .tour-hotspot-arrow:hover,
    .tour-hotspot-arrow:focus,
    .tour-hotspot-info:hover,
    .tour-hotspot-info:focus {
        transform: scale(1.2);
        box-shadow: 0 0 0 4px rgba(255, 255, 255, .45), 0 2px 8px rgba(0, 0, 0, .4);
        outline: none;
    }
    @keyframes tour-pulse {
        0%, 100% { box-shadow: 0 2px 8px rgba(0, 0, 0, .35), 0 0 0 0 rgba(37, 99, 235, .5); }
        50% { box-shadow: 0 2px 8px rgba(0, 0, 0, .35), 0 0 0 10px rgba(37, 99, 235, 0); }
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
</style>

<div class="relative">
    <div id="{{ $viewerId }}-inline" class="w-full h-[320px] sm:h-[420px] rounded-xl overflow-hidden bg-slate-950" aria-label="360 degree virtual tour preview"></div>
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
        <div class="flex items-start justify-between gap-3">
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
            scenes: scenes,
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
            button.innerHTML = '<span class="block truncate text-xs font-medium">' + escapeHtml(scene.title || 'Scene') + '</span>';
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
    document.addEventListener('DOMContentLoaded', function () {
        renderThumbnails();
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
        if (inlineViewer && scenes[sceneId]) inlineViewer.loadScene(sceneId);
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
