{{--
    Reusable Street-View-style 360 viewer.
    Required: $tour (VirtualTour, with scenes.hotspots eager loaded), $viewerId (unique string per page)
--}}
@php
    $scenesJson = $tour->scenes->mapWithKeys(fn ($s) => [
        'scene-'.$s->id => [
            'title' => $s->title,
            'type' => 'equirectangular',
            'panorama' => asset('storage/'.$s->panorama_image),
            'hotSpots' => $s->hotspots->map(fn ($h) => [
                'pitch' => (float) $h->position_y,
                'yaw' => (float) $h->position_x,
                'type' => $h->target_scene_id ? 'scene' : 'info',
                'text' => $h->label ?: ($h->target_scene_id ? 'Go this way' : 'Info'),
                'sceneId' => $h->target_scene_id ? 'scene-'.$h->target_scene_id : null,
                'cssClass' => $h->target_scene_id ? 'tour-hotspot-arrow' : 'tour-hotspot-info',
            ])->toArray(),
        ],
    ])->toJson();
    $firstSceneId = 'scene-'.$tour->scenes->first()->id;
@endphp

<style>
    /* Arrow-styled navigation hotspots instead of Pannellum's plain dot,
       so moving between scenes reads like Street View's floor arrows. */
    .tour-hotspot-arrow {
        width: 32px; height: 32px;
        background: #2563eb;
        border: 2px solid white;
        border-radius: 9999px;
        box-shadow: 0 2px 8px rgba(0,0,0,.35);
        cursor: pointer;
        animation: tour-pulse 1.8s ease-in-out infinite;
        transition: transform .15s ease;
    }
    .tour-hotspot-arrow:hover {
        transform: scale(1.25);
        animation-play-state: paused;
    }
    @keyframes tour-pulse {
        0%, 100% { box-shadow: 0 2px 8px rgba(0,0,0,.35), 0 0 0 0 rgba(37,99,235,.5); }
        50% { box-shadow: 0 2px 8px rgba(0,0,0,.35), 0 0 0 10px rgba(37,99,235,0); }
    }
    .tour-hotspot-arrow::after {
        content: '';
        position: absolute; inset: 0;
        margin: auto; width: 10px; height: 10px;
        border-top: 3px solid white; border-right: 3px solid white;
        transform: rotate(45deg);
    }
    .tour-hotspot-info {
        width: 26px; height: 26px;
        background: #0f172a;
        border: 2px solid white;
        border-radius: 9999px;
        cursor: pointer;
    }
    .tour-room-label {
        position: absolute; top: 12px; left: 12px; z-index: 10;
        background: rgba(15,23,42,.75); color: white;
        font-size: .8rem; padding: 4px 10px; border-radius: 9999px;
        pointer-events: none;
    }
    .tour-exit-btn {
        position: absolute; top: 12px; right: 12px; z-index: 20;
    }
</style>

<div class="relative">
    <div id="{{ $viewerId }}-inline" style="width:100%;height:420px;border-radius:0.75rem;overflow:hidden;"></div>
    <div class="mt-3 flex items-center justify-between">
        <div class="flex flex-wrap gap-2">
            @foreach ($tour->scenes as $s)
                <button type="button" onclick="window['{{ $viewerId }}_goto']('scene-{{ $s->id }}')"
                    class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full px-3 py-1.5">
                    {{ $s->title }}
                </button>
            @endforeach
        </div>
        <button type="button" onclick="window['{{ $viewerId }}_openFullscreen']()"
            class="text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-full px-4 py-1.5 whitespace-nowrap">
            View Fullscreen Tour
        </button>
    </div>
</div>

{{-- Immersive fullscreen overlay, opened on demand --}}
<div id="{{ $viewerId }}-overlay" class="fixed inset-0 bg-black z-50 hidden">
    <div class="tour-room-label" id="{{ $viewerId }}-room-label"></div>
    <button type="button" onclick="window['{{ $viewerId }}_closeFullscreen']()"
        class="tour-exit-btn bg-white/90 hover:bg-white text-slate-900 text-sm font-medium rounded-full px-4 py-2 shadow">
        ✕ Exit Virtual Tour
    </button>
    <div id="{{ $viewerId }}-full" style="width:100%;height:100%;"></div>
</div>

<script>
(function () {
    const scenes = {!! $scenesJson !!};
    const firstScene = '{{ $firstSceneId }}';
    let inlineViewer, fullViewer;

    function baseConfig(container) {
        return {
            default: {
                firstScene: firstScene,
                sceneFadeDuration: 700,
                autoLoad: true,
                showZoomCtrl: true,
                showFullscreenCtrl: false, // we provide our own immersive overlay instead
                compass: false,
                hotSpotDebug: false,
            },
            scenes: scenes,
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        inlineViewer = pannellum.viewer('{{ $viewerId }}-inline', baseConfig());
    });

    window['{{ $viewerId }}_goto'] = function (sceneId) {
        if (inlineViewer) inlineViewer.loadScene(sceneId);
    };

    window['{{ $viewerId }}_openFullscreen'] = function () {
        const overlay = document.getElementById('{{ $viewerId }}-overlay');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        if (!fullViewer) {
            fullViewer = pannellum.viewer('{{ $viewerId }}-full', baseConfig());
            fullViewer.on('scenechange', function (sceneId) {
                const label = document.getElementById('{{ $viewerId }}-room-label');
                label.textContent = scenes[sceneId] ? scenes[sceneId].title : '';
            });
            setTimeout(function () {
                const label = document.getElementById('{{ $viewerId }}-room-label');
                label.textContent = scenes[firstScene].title;
            }, 300);
        }
    };

    window['{{ $viewerId }}_closeFullscreen'] = function () {
        document.getElementById('{{ $viewerId }}-overlay').classList.add('hidden');
        document.body.style.overflow = '';
    };

    // Esc key also exits, matching Street-View-style conventions.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window['{{ $viewerId }}_closeFullscreen']();
    });
})();
</script>
