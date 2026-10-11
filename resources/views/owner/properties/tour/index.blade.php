@extends('layouts.app')

@section('title', $property->name.' — Virtual Tour')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <style>
        /* Draggable placement arrow (owner editor only). Do not set `transform` here:
           Pannellum positions hotspots with it. */
        .tour-placement-marker {
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
            outline: 3px solid #2563eb;
            outline-offset: 3px;
        }
        .tour-placement-marker.is-dragging { cursor: grabbing; outline-color: #f59e0b; }
    </style>
@endpush

@section('content')
    <a href="{{ route('owner.properties.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>My Properties</span></a>
    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-6">{{ $property->name }} — Virtual Tour</h1>

    @if (! $tour)
        {{-- No tour yet: just create the shell (title/description/thumbnail) --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 max-w-lg">
            <p class="text-sm text-slate-500 mb-4">Create a virtual tour, then add 360° scenes below.</p>
            <form method="POST" action="{{ route('owner.properties.tour.store', $property) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tour title</label>
                    <input type="text" name="title" value="{{ old('title', $property->name.' Virtual Tour') }}" required
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Thumbnail (optional)</label>
                    <input type="file" name="thumbnail" accept="image/*" class="w-full text-sm">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
                    Create tour
                </button>
            </form>
        </div>
    @else
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="text-xs px-2 py-1 rounded-full {{ $tour->status === 'published' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                    {{ str($tour->status)->title() }}
                </span>
                <span class="text-sm text-slate-500 ml-2">{{ $tour->scenes->count() }} scene(s)</span>
            </div>
            @if ($tour->status !== 'published')
                <form method="POST" action="{{ route('owner.properties.tour.publish', $property) }}">
                    @csrf @method('PATCH')
                    <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
                        Publish tour
                    </button>
                </form>
            @endif
        </div>

        {{-- Live preview of the whole tour, scene switching + hotspot navigation --}}
        @if ($tour->scenes->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-4 mb-8">
                <p class="text-sm font-medium text-slate-700 mb-3">Preview — this is exactly what renters will see once published</p>
                @include('partials.virtual-tour-viewer', ['tour' => $tour, 'viewerId' => 'ownerTour'])
            </div>
        @endif

        {{-- Scene list + add-scene form --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-medium text-slate-900 mb-4">Add a scene</h2>
                <form method="POST" action="{{ route('owner.properties.tour.scenes.store', [$property, $tour]) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Scene title</label>
                        <input type="text" name="title" required placeholder="e.g. Living Room, Room 101, Common Area"
                            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">360° / panorama image</label>
                        <input type="file" name="panorama_image" accept="image/*" required class="w-full text-sm">
                        <p class="text-xs text-slate-400 mt-1">Use an equirectangular 360° photo for the best result.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
                        Add scene
                    </button>
                </form>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-medium text-slate-900 mb-4">Scenes</h2>
                @if ($tour->scenes->isEmpty())
                    <p class="text-sm text-slate-500">No scenes yet — add your first one.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($tour->scenes as $scene)
                            <div class="border border-slate-200 rounded-lg p-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ asset('storage/'.$scene->panorama_image) }}" class="w-16 h-12 object-cover rounded">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-slate-900">{{ $scene->title }}</p>
                                        <p class="text-xs text-slate-400">{{ $scene->hotspots->count() }} hotspot(s)</p>
                                    </div>
                                    <form action="{{ route('owner.properties.tour.scenes.destroy', [$property, $tour, $scene]) }}" method="POST"
                                          onsubmit="return confirm('Remove this scene?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 text-xs hover:underline">Remove</button>
                                    </form>
                                </div>

                                {{-- Add hotspot to this scene --}}
                                <details class="mt-3">
                                    <summary class="text-xs text-blue-600 cursor-pointer">+ Add hotspot (link to another scene)</summary>
                                    <p class="text-xs text-slate-400 mt-2">
                                        Click <strong>Place arrow on preview</strong>, then click the panorama where the
                                        arrow should appear. You can drag the panorama first to find the correct view.
                                    </p>
                                    <form action="{{ route('owner.properties.tour.hotspots.store', [$property, $tour, $scene]) }}" method="POST"
                                          class="grid grid-cols-2 gap-2 mt-2">
                                        @csrf
                                        <select name="target_scene_id" class="col-span-2 rounded-lg border-slate-300 text-xs">
                                            <option value="">Info hotspot (no navigation)</option>
                                            @foreach ($tour->scenes as $target)
                                                @if ($target->id !== $scene->id)
                                                    <option value="{{ $target->id }}">Go to: {{ $target->title }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <input type="text" name="label" placeholder="Label (e.g. To Kitchen)" class="rounded-lg border-slate-300 text-xs">
                                        <input type="hidden" name="type" value="navigation">
                                        <input id="hotspot-yaw-{{ $scene->id }}" type="number" step="0.01" name="position_x" placeholder="Yaw (-180 to 180)" required
                                            class="rounded-lg border-slate-300 text-xs">
                                        <input id="hotspot-pitch-{{ $scene->id }}" type="number" step="0.01" name="position_y" placeholder="Pitch, e.g. -50 for floor" required
                                            class="rounded-lg border-slate-300 text-xs">
                                        <div class="col-span-2">
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="hotspot-size-{{ $scene->id }}" class="text-xs font-medium text-slate-700">Arrow size</label>
                                                <output id="hotspot-size-out-{{ $scene->id }}" class="text-xs text-slate-500">56 px</output>
                                            </div>
                                            <input id="hotspot-size-{{ $scene->id }}" type="range" name="size" min="24" max="140" step="2" value="56"
                                                data-size-slider="{{ $scene->id }}" class="w-full">
                                            <div class="flex gap-2 mt-1">
                                                @foreach ([['Small', 36], ['Medium', 56], ['Large', 90]] as [$sizeLabel, $sizePx])
                                                    <button type="button" data-size-preset="{{ $scene->id }}" data-size="{{ $sizePx }}"
                                                        class="flex-1 border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs rounded-lg py-1">{{ $sizeLabel }}</button>
                                                @endforeach
                                            </div>
                                        </div>
                                        <button type="button" data-place-hotspot="{{ $scene->id }}"
                                            class="col-span-2 border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs rounded-lg py-1.5">
                                            Place arrow on preview
                                        </button>
                                        <button type="submit" class="col-span-2 bg-slate-800 hover:bg-slate-900 text-white text-xs rounded-lg py-1.5">
                                            Add hotspot
                                        </button>
                                    </form>
                                </details>

                                @if ($scene->hotspots->isNotEmpty())
                                    <ul class="mt-2 text-xs text-slate-500 space-y-2">
                                        @foreach ($scene->hotspots as $hotspot)
                                            <li>
                                                <div class="flex items-center justify-between">
                                                    <span>{{ $hotspot->label ?? 'Hotspot' }} (yaw {{ $hotspot->position_x }}, pitch {{ $hotspot->position_y }})</span>
                                                    <form action="{{ route('owner.properties.tour.hotspots.destroy', [$property, $tour, $scene, $hotspot]) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <button class="text-red-500 hover:underline">remove</button>
                                                    </form>
                                                </div>
                                                @if ($hotspot->target_scene_id)
                                                    <form action="{{ route('owner.properties.tour.hotspots.update', [$property, $tour, $scene, $hotspot]) }}" method="POST"
                                                          class="flex items-center gap-2 mt-1">
                                                        @csrf @method('PATCH')
                                                        <span class="text-slate-400">Size</span>
                                                        <input type="range" name="size" min="24" max="140" step="2" value="{{ $hotspot->size ?? 56 }}"
                                                            data-saved-size="{{ $hotspot->id }}" data-saved-scene="{{ $scene->id }}" class="flex-1">
                                                        <output class="w-10 text-right">{{ $hotspot->size ?? 56 }}px</output>
                                                        <button class="text-blue-600 hover:underline">save</button>
                                                    </form>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

@if ($tour && $tour->scenes->isNotEmpty())
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewer = window['ownerTour_getInlineViewer'] && window['ownerTour_getInlineViewer']();
            const preview = document.getElementById('ownerTour-inline');
            if (!viewer || !preview) {
                // Don't fail silently: tell the owner and log the reason.
                console.error('[tour editor] 360 viewer not ready.',
                    { pannellumLoaded: typeof window.pannellum !== 'undefined',
                      getInlineViewer: typeof window['ownerTour_getInlineViewer'],
                      previewElement: !!preview });
                document.querySelectorAll('[data-place-hotspot]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        b.textContent = 'Preview not loaded — press Ctrl+F5 (see console F12)';
                    });
                });
                return;
            }

            const MARKER_ID = 'owner-placement-marker';
            const buttons = document.querySelectorAll('[data-place-hotspot]');
            const originalLabels = new Map();
            buttons.forEach(function (b) { originalLabels.set(b, b.textContent.trim()); });

            let activeSceneId = null;   // numeric scene id (string) we are placing an arrow in
            let activeButton = null;
            let markerHs = null;        // the Pannellum hotspot config object (mutated while dragging)
            let markerSceneKey = null;  // e.g. "scene-3"
            let markerDiv = null;       // DOM element of the marker (for live resizing)
            let dragging = false;
            let nudge = 0.0005;
            let downPos = null;

            // ---- helpers -------------------------------------------------------------
            function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }

            function normalizeYaw(yaw) {
                while (yaw > 180) yaw -= 360;
                while (yaw < -180) yaw += 360;
                return yaw;
            }

            // Pannellum's real API is mouseEventToCoords(event) -> [pitch, yaw].
            function coordsFromEvent(event) {
                if (typeof viewer.mouseEventToCoords !== 'function') return null;
                const c = viewer.mouseEventToCoords(event);
                if (!c) return null;
                const pitch = Array.isArray(c) ? c[0] : c.pitch;
                const yaw = Array.isArray(c) ? c[1] : c.yaw;
                if (!isFinite(pitch) || !isFinite(yaw)) return null;
                return { pitch: pitch, yaw: yaw };
            }

            function fillInputs(pitch, yaw) {
                const yawInput = document.getElementById('hotspot-yaw-' + activeSceneId);
                const pitchInput = document.getElementById('hotspot-pitch-' + activeSceneId);
                if (yawInput) yawInput.value = yaw.toFixed(2);
                if (pitchInput) pitchInput.value = pitch.toFixed(2);
            }

            // Pannellum only re-draws hotspots when the view changes, so nudge it by a
            // tiny (invisible) amount after we move the marker's coordinates.
            function redraw() {
                nudge = -nudge;
                viewer.setYaw(viewer.getYaw() + nudge, false);
            }

            function setMarkerPosition(pitch, yaw) {
                if (!markerHs) return;
                pitch = clamp(pitch, -90, 90);
                yaw = normalizeYaw(yaw);
                markerHs.pitch = pitch;
                markerHs.yaw = yaw;
                fillInputs(pitch, yaw);
                redraw();
            }

            function setButtonLabel(text) {
                if (activeButton) activeButton.textContent = text;
            }

            function resetButtons() {
                buttons.forEach(function (b) {
                    b.classList.remove('ring-2', 'ring-blue-500');
                    b.textContent = originalLabels.get(b);
                });
                preview.classList.remove('ring-4', 'ring-blue-200');
            }

            function removeMarker() {
                if (markerHs && markerSceneKey) {
                    try { viewer.removeHotSpot(MARKER_ID, markerSceneKey); } catch (e) { /* already gone */ }
                }
                markerHs = null;
                markerSceneKey = null;
                markerDiv = null;
            }

            // ---- dragging the marker ---------------------------------------------------
            function currentSize() {
                const slider = document.getElementById('hotspot-size-' + activeSceneId);
                return slider ? Number(slider.value) : 56;
            }

            function bindMarkerDrag(div) {
                markerDiv = div;
                div.style.setProperty('--arrow-size', currentSize() + 'px');
                div.title = 'Drag to move the arrow';
                div.style.touchAction = 'none';

                // Keep Pannellum from treating a marker drag as a panorama pan.
                ['mousedown', 'touchstart', 'click', 'dblclick'].forEach(function (type) {
                    div.addEventListener(type, function (e) { e.stopPropagation(); });
                });

                div.addEventListener('pointerdown', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dragging = true;
                    div.classList.add('is-dragging');
                    try { div.setPointerCapture(e.pointerId); } catch (err) { /* not critical */ }
                });

                div.addEventListener('pointermove', function (e) {
                    if (!dragging) return;
                    e.preventDefault();
                    e.stopPropagation();
                    const c = coordsFromEvent(e);
                    if (c) setMarkerPosition(c.pitch, c.yaw);
                });

                function endDrag(e) {
                    if (!dragging) return;
                    dragging = false;
                    div.classList.remove('is-dragging');
                    try { div.releasePointerCapture(e.pointerId); } catch (err) { /* not critical */ }
                    setButtonLabel('Arrow placed — drag it, or click the panorama to move it');
                }
                div.addEventListener('pointerup', endDrag);
                div.addEventListener('pointercancel', endDrag);
            }

            // ---- placement flow -------------------------------------------------------
            function addMarkerAtViewCenter() {
                const pitch = clamp(viewer.getPitch(), -90, 90);
                const yaw = normalizeYaw(viewer.getYaw());

                markerHs = {
                    id: MARKER_ID,
                    pitch: pitch,
                    yaw: yaw,
                    type: 'custom',
                    cssClass: 'tour-placement-marker',
                    createTooltipFunc: bindMarkerDrag
                };
                markerSceneKey = 'scene-' + activeSceneId;
                viewer.addHotSpot(markerHs, markerSceneKey);

                // Pannellum creates new hotspots hidden and only reveals them on the next
                // render, so force one — otherwise the arrow stays invisible until you pan.
                redraw();

                fillInputs(pitch, yaw);
                if (markerHs.div) {
                    setButtonLabel('Arrow placed — drag it, or click the panorama to move it');
                } else {
                    console.error('[tour editor] arrow element was not created; is the panorama loaded?');
                    setButtonLabel('Could not draw the arrow — the panorama is not loaded (see console F12)');
                }
            }

            function startPlacement(sceneId, button) {
                removeMarker();
                resetButtons();

                activeSceneId = String(sceneId);
                activeButton = button;
                button.classList.add('ring-2', 'ring-blue-500');
                preview.classList.add('ring-4', 'ring-blue-200');
                button.textContent = 'Loading scene…';

                // The preview sits above the forms — bring it into view.
                preview.scrollIntoView({ behavior: 'smooth', block: 'center' });

                const key = 'scene-' + activeSceneId;
                if (viewer.getScene() === key) {
                    addMarkerAtViewCenter();
                    return;
                }

                const onLoad = function () {
                    viewer.off('load', onLoad);
                    if (activeSceneId !== null && 'scene-' + activeSceneId === key) addMarkerAtViewCenter();
                };
                viewer.on('load', onLoad);
                viewer.loadScene(key);

                window.setTimeout(function () {
                    if (activeButton === button && button.textContent === 'Loading scene…') {
                        viewer.off('load', onLoad);
                        button.textContent = 'Scene did not load — open the panorama image URL to check it exists';
                    }
                }, 10000);
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    try {
                        startPlacement(this.dataset.placeHotspot, this);
                    } catch (err) {
                        console.error('[tour editor] could not place arrow:', err);
                        this.textContent = 'Error: ' + (err && err.message ? err.message : err) + ' (see console F12)';
                    }
                });
            });

            // ---- arrow size (new arrow) ---------------------------------------------------
            document.querySelectorAll('[data-size-slider]').forEach(function (slider) {
                slider.addEventListener('input', function () {
                    const out = document.getElementById('hotspot-size-out-' + slider.dataset.sizeSlider);
                    if (out) out.textContent = slider.value + ' px';
                    if (activeSceneId === slider.dataset.sizeSlider && markerDiv) {
                        markerDiv.style.setProperty('--arrow-size', slider.value + 'px');
                        redraw(); // Pannellum re-centres the hotspot using its new width
                    }
                });
            });
            document.querySelectorAll('[data-size-preset]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const slider = document.getElementById('hotspot-size-' + btn.dataset.sizePreset);
                    if (!slider) return;
                    slider.value = btn.dataset.size;
                    slider.dispatchEvent(new Event('input'));
                });
            });

            // ---- arrow size (already-saved arrows): live preview, saved with the "save" button ----
            function applySavedSize(hotspotId, sceneKey, size) {
                const apply = function () {
                    const cfg = typeof viewer.getConfig === 'function' ? viewer.getConfig() : null;
                    const hs = cfg && (cfg.hotSpots || []).find(function (h) { return h.id === 'hotspot-' + hotspotId; });
                    if (hs && hs.div) {
                        hs.div.style.setProperty('--arrow-size', size + 'px');
                        redraw();
                    }
                };
                if (viewer.getScene() === sceneKey) { apply(); return; }
                const onLoad = function () { viewer.off('load', onLoad); apply(); };
                viewer.on('load', onLoad);
                viewer.loadScene(sceneKey);
            }
            document.querySelectorAll('[data-saved-size]').forEach(function (slider) {
                slider.addEventListener('input', function () {
                    const out = slider.parentElement.querySelector('output');
                    if (out) out.textContent = slider.value + 'px';
                    applySavedSize(slider.dataset.savedSize, 'scene-' + slider.dataset.savedScene, slider.value);
                });
            });

            viewer.on('error', function (message) {
                console.error('[tour editor] Pannellum error:', message);
                if (activeButton) activeButton.textContent = 'Viewer error: ' + message;
            });

            // If the owner switches to another scene, drop the pending marker.
            viewer.on('scenechange', function (sceneId) {
                if (activeSceneId !== null && sceneId !== 'scene-' + activeSceneId) {
                    removeMarker();
                    resetButtons();
                    activeSceneId = null;
                    activeButton = null;
                }
            });

            // Click (not drag-pan) on the panorama moves the marker there.
            preview.addEventListener('pointerdown', function (e) {
                downPos = { x: e.clientX, y: e.clientY };
            });
            preview.addEventListener('click', function (event) {
                if (activeSceneId === null || !markerHs || dragging) return;
                if (event.target.closest('.pnlm-hotspot-base, .pnlm-controls-container')) return;
                if (downPos && Math.hypot(event.clientX - downPos.x, event.clientY - downPos.y) > 5) return; // it was a pan
                const c = coordsFromEvent(event);
                if (!c) return;
                setMarkerPosition(c.pitch, c.yaw);
                setButtonLabel('Arrow placed — drag it, or click the panorama to move it');
            });
        });
        </script>
    @endpush
@endif
