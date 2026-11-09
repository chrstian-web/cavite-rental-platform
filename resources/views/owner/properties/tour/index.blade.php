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
        #hotspot-editor-viewer-slot .tour-inline-viewer {
            position: absolute !important;
            inset: 0;
            width: 100vw !important;
            height: 100dvh !important;
            min-height: 100dvh !important;
            border-radius: 0 !important;
        }
        @supports not (height: 100dvh) {
            #hotspot-editor-viewer-slot .tour-inline-viewer { height: 100vh !important; min-height: 100vh !important; }
        }
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
                                    <div class="flex items-center gap-2">
                                        <details class="relative">
                                            <summary class="inline-flex min-h-10 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">Edit</summary>
                                            <div class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-xl">
                                                <form action="{{ route('owner.properties.tour.scenes.update', [$property, $tour, $scene]) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                                    @csrf @method('PUT')
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-600 mb-1">Scene title</label>
                                                        <input type="text" name="title" value="{{ $scene->title }}" required class="w-full rounded-lg border-slate-300 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-600 mb-1">Replace panorama image</label>
                                                        <input type="file" name="panorama_image" accept="image/*" class="w-full text-xs">
                                                        <p class="mt-1 text-[11px] text-slate-400">Leave empty to keep the current image.</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                                                        <textarea name="description" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ $scene->description }}</textarea>
                                                    </div>
                                                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Save changes</button>
                                                </form>
                                            </div>
                                        </details>
                                        <form action="{{ route('owner.properties.tour.scenes.destroy', [$property, $tour, $scene]) }}" method="POST"
                                              onsubmit="return confirm('Remove this scene?')">
                                            @csrf @method('DELETE')
                                            <x-delete-button label="Remove" />
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-col gap-2 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs text-slate-500">Place a navigation arrow or information marker on this scene.</p>
                                    <button type="button" data-open-hotspot-editor="{{ $scene->id }}" data-scene-title="{{ $scene->title }}"
                                        class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                                        <span aria-hidden="true">＋</span> Add hotspot
                                    </button>
                                </div>

                                @if ($scene->hotspots->isNotEmpty())
                                    <ul class="mt-2 text-xs text-slate-500 space-y-2">
                                        @foreach ($scene->hotspots as $hotspot)
                                            <li>
                                                <div class="flex items-center justify-between">
                                                    <span>{{ $hotspot->label ?? 'Hotspot' }} (yaw {{ $hotspot->position_x }}, pitch {{ $hotspot->position_y }})</span>
                                                    <form action="{{ route('owner.properties.tour.hotspots.destroy', [$property, $tour, $scene, $hotspot]) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <x-delete-button label="Remove" size="sm" />
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
                                                        <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 rt-soft">Save</button>
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

        <div id="hotspot-editor-form-storage" class="hidden" aria-hidden="true">
            @foreach ($tour->scenes as $scene)
                <form action="{{ route('owner.properties.tour.hotspots.store', [$property, $tour, $scene]) }}" method="POST"
                      data-hotspot-form="{{ $scene->id }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="hotspot-target-{{ $scene->id }}" class="mb-1 block text-xs font-semibold text-slate-200">Hotspot action</label>
                        <select id="hotspot-target-{{ $scene->id }}" name="target_scene_id" data-hotspot-target
                                class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Information marker — no scene change</option>
                            @foreach ($tour->scenes as $target)
                                @if ($target->id !== $scene->id)
                                    <option value="{{ $target->id }}">Navigate to: {{ $target->title }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" name="type" value="info">
                    </div>
                    <div>
                        <label for="hotspot-label-{{ $scene->id }}" class="mb-1 block text-xs font-semibold text-slate-200">Label <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="hotspot-label-{{ $scene->id }}" type="text" name="label" maxlength="100" placeholder="e.g. To Kitchen or Large window"
                               class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="hotspot-yaw-{{ $scene->id }}" class="mb-1 block text-xs font-semibold text-slate-200">Yaw</label>
                            <input id="hotspot-yaw-{{ $scene->id }}" type="number" step="0.01" min="-180" max="180" name="position_x" placeholder="-180 to 180" required
                                   class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="hotspot-pitch-{{ $scene->id }}" class="mb-1 block text-xs font-semibold text-slate-200">Pitch</label>
                            <input id="hotspot-pitch-{{ $scene->id }}" type="number" step="0.01" min="-90" max="90" name="position_y" placeholder="-90 to 90" required
                                   class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label for="hotspot-size-{{ $scene->id }}" class="text-xs font-semibold text-slate-200">Hotspot size</label>
                            <output id="hotspot-size-out-{{ $scene->id }}" class="text-xs text-slate-300">56 px</output>
                        </div>
                        <input id="hotspot-size-{{ $scene->id }}" type="range" name="size" min="24" max="140" step="2" value="56"
                               data-size-slider="{{ $scene->id }}" class="w-full accent-blue-500">
                        <div class="mt-2 flex gap-2">
                            @foreach ([['Small', 36], ['Medium', 56], ['Large', 90]] as [$sizeLabel, $sizePx])
                                <button type="button" data-size-preset="{{ $scene->id }}" data-size="{{ $sizePx }}"
                                        class="flex-1 rounded-lg border border-white/15 bg-white/5 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-white/10">{{ $sizeLabel }}</button>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                        Save hotspot
                    </button>
                </form>
            @endforeach
        </div>

        <div id="hotspot-editor-overlay" class="fixed inset-0 z-[80] hidden overflow-hidden bg-slate-950" role="dialog" aria-modal="true" aria-labelledby="hotspot-editor-title">
            <div id="hotspot-editor-viewer-slot" class="absolute inset-0 overflow-hidden bg-slate-950"></div>
            <div class="pointer-events-none absolute inset-x-0 top-0 z-20 flex items-start justify-between gap-3 bg-gradient-to-b from-black/75 to-transparent p-4 sm:p-6">
                <div class="pointer-events-auto min-w-0">
                    <h2 id="hotspot-editor-title" class="truncate text-base font-semibold text-white sm:text-lg">Place a hotspot</h2>
                    <p id="hotspot-editor-scene-title" class="mt-1 truncate text-xs text-white/70"></p>
                </div>
                <button type="button" data-close-hotspot-editor class="pointer-events-auto inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border border-white/20 bg-black/50 px-4 py-2 text-sm font-semibold text-white transition hover:bg-black/75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <span aria-hidden="true">✕</span> Close
                </button>
            </div>
            <aside id="hotspot-editor-settings" class="absolute inset-x-3 bottom-3 z-30 max-h-[58dvh] overflow-y-auto rounded-2xl border border-white/15 bg-slate-900/95 p-4 text-white shadow-2xl backdrop-blur-xl sm:inset-x-auto sm:right-5 sm:top-20 sm:bottom-auto sm:max-h-[calc(100dvh-6rem)] sm:w-[min(26rem,calc(100vw-2.5rem))] sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-white">Hotspot settings</h3>
                    <button type="button" data-toggle-hotspot-settings class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border border-white/15 px-2 text-lg leading-none text-slate-200 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Minimize hotspot settings" aria-expanded="true" title="Minimize hotspot settings">
                        <span data-hotspot-settings-icon aria-hidden="true">−</span>
                    </button>
                </div>
                <div id="hotspot-editor-settings-body">
                    <p class="mt-1 text-xs leading-relaxed text-slate-300">Click the panorama to place or move the marker. Drag the arrow to fine-tune it.</p>
                    <p id="hotspot-editor-status" class="mt-2 rounded-lg bg-blue-500/10 px-3 py-2 text-xs text-blue-100" role="status" aria-live="polite">Loading selected scene…</p>
                    <div id="hotspot-editor-form-slot" class="mt-4"></div>
                    <button type="button" data-close-hotspot-editor class="mt-3 min-h-10 w-full rounded-lg border border-white/15 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Cancel</button>
                </div>
            </aside>
        </div>
    @endif
@endsection

@if ($tour && $tour->scenes->isNotEmpty())
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewer = window['ownerTour_getInlineViewer'] && window['ownerTour_getInlineViewer']();
            const preview = document.getElementById('ownerTour-inline');
            const editor = document.getElementById('hotspot-editor-overlay');
            const viewerSlot = document.getElementById('hotspot-editor-viewer-slot');
            const formSlot = document.getElementById('hotspot-editor-form-slot');
            const formStorage = document.getElementById('hotspot-editor-form-storage');
            const statusElement = document.getElementById('hotspot-editor-status');
            const sceneTitleElement = document.getElementById('hotspot-editor-scene-title');
            const settingsBody = document.getElementById('hotspot-editor-settings-body');
            const settingsToggle = document.querySelector('[data-toggle-hotspot-settings]');
            const settingsIcon = document.querySelector('[data-hotspot-settings-icon]');
            const launchButtons = document.querySelectorAll('[data-open-hotspot-editor]');
            const closeButtons = document.querySelectorAll('[data-close-hotspot-editor]');
            const previewHome = preview ? preview.parentElement : null;
            const previewNextSibling = preview ? preview.nextSibling : null;

            if (!viewer || !preview || !editor || !viewerSlot || !formSlot || !formStorage) {
                console.error('[tour editor] The 360 viewer or hotspot editor could not be initialized.');
                launchButtons.forEach(function (button) {
                    button.disabled = true;
                    button.title = 'The 360° viewer is unavailable. Refresh the page and try again.';
                    button.classList.add('cursor-not-allowed', 'opacity-60');
                });
                return;
            }

            const MARKER_ID = 'owner-placement-marker';
            let activeSceneId = null;   // numeric scene id (string) we are placing an arrow in
            let activeTrigger = null;
            let activeForm = null;
            let markerHs = null;        // the Pannellum hotspot config object (mutated while dragging)
            let markerSceneKey = null;  // e.g. "scene-3"
            let markerDiv = null;       // DOM element of the marker (for live resizing)
            let dragging = false;
            let nudge = 0.0005;
            let downPos = null;
            let previousBodyOverflow = '';
            let pendingLoadHandler = null;
            let settingsCollapsed = false;

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
                if (statusElement) statusElement.textContent = text;
            }

            function setSettingsCollapsed(collapsed) {
                settingsCollapsed = collapsed;
                if (settingsBody) {
                    settingsBody.hidden = collapsed;
                    settingsBody.style.display = collapsed ? 'none' : '';
                }
                if (settingsToggle) {
                    settingsToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    settingsToggle.setAttribute('aria-label', collapsed ? 'Expand hotspot settings' : 'Minimize hotspot settings');
                    settingsToggle.title = collapsed ? 'Expand hotspot settings' : 'Minimize hotspot settings';
                }
                if (settingsIcon) settingsIcon.textContent = collapsed ? '+' : '−';
            }

            function resetButtons() {
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

            function closeEditor() {
                if (editor.classList.contains('hidden')) return;

                if (pendingLoadHandler) {
                    viewer.off('load', pendingLoadHandler);
                    pendingLoadHandler = null;
                }
                removeMarker();
                resetButtons();
                if (activeForm) formStorage.appendChild(activeForm);
                activeForm = null;

                if (previewHome) {
                    const nextSibling = previewNextSibling && previewNextSibling.parentNode === previewHome
                        ? previewNextSibling
                        : null;
                    previewHome.insertBefore(preview, nextSibling);
                }

                editor.classList.add('hidden');
                document.body.style.overflow = previousBodyOverflow;
                viewer.resize();

                const restoreFocus = activeTrigger;
                activeTrigger = null;
                activeSceneId = null;
                if (restoreFocus && typeof restoreFocus.focus === 'function') restoreFocus.focus();
            }

            function openEditor(trigger) {
                if (!editor.classList.contains('hidden')) closeEditor();

                const sceneId = String(trigger.dataset.openHotspotEditor);
                const form = Array.from(formStorage.querySelectorAll('[data-hotspot-form]'))
                    .find(function (candidate) { return candidate.dataset.hotspotForm === sceneId; });
                if (!form) {
                    console.error('[tour editor] No hotspot form found for scene ' + sceneId);
                    return;
                }

                activeSceneId = sceneId;
                activeTrigger = trigger;
                activeForm = form;
                form.reset();
                const sizeSlider = form.querySelector('[data-size-slider]');
                const sizeOutput = form.querySelector('output');
                if (sizeSlider && sizeOutput) sizeOutput.textContent = sizeSlider.value + ' px';

                const targetSelect = form.querySelector('[data-hotspot-target]');
                const typeInput = form.querySelector('[name="type"]');
                if (targetSelect && typeInput) typeInput.value = targetSelect.value ? 'navigation' : 'info';

                sceneTitleElement.textContent = 'Adding a hotspot to ' + (trigger.dataset.sceneTitle || 'this scene');
                formSlot.appendChild(form);
                viewerSlot.appendChild(preview);
                previousBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                editor.classList.remove('hidden');
                setSettingsCollapsed(false);
                setButtonLabel('Loading selected scene…');

                window.requestAnimationFrame(function () {
                    if (editor.classList.contains('hidden') || activeForm !== form) return;
                    viewer.resize();
                    startPlacement(sceneId);
                });
                const closeButton = editor.querySelector('[data-close-hotspot-editor]');
                if (closeButton) closeButton.focus();
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

            function startPlacement(sceneId) {
                if (pendingLoadHandler) {
                    viewer.off('load', pendingLoadHandler);
                    pendingLoadHandler = null;
                }
                removeMarker();
                resetButtons();

                activeSceneId = String(sceneId);
                setButtonLabel('Loading selected scene…');
                viewer.resize();

                const key = 'scene-' + activeSceneId;
                if (viewer.getScene() === key) {
                    addMarkerAtViewCenter();
                    return;
                }

                const onLoad = function () {
                    viewer.off('load', onLoad);
                    if (pendingLoadHandler === onLoad) pendingLoadHandler = null;
                    if (activeSceneId !== null && 'scene-' + activeSceneId === key) addMarkerAtViewCenter();
                };
                pendingLoadHandler = onLoad;
                viewer.on('load', onLoad);
                viewer.loadScene(key);

                window.setTimeout(function () {
                    if (activeSceneId === String(sceneId) && statusElement.textContent === 'Loading selected scene…') {
                        viewer.off('load', onLoad);
                        if (pendingLoadHandler === onLoad) pendingLoadHandler = null;
                        setButtonLabel('The scene could not load. Check that the panorama image is available, then reopen the editor.');
                    }
                }, 10000);
            }

            launchButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    try {
                        openEditor(this);
                    } catch (err) {
                        console.error('[tour editor] could not open hotspot editor:', err);
                        setButtonLabel('Could not open the editor. Please refresh and try again.');
                    }
                });
            });

            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeEditor);
            });
            if (settingsToggle) {
                settingsToggle.addEventListener('click', function () {
                    setSettingsCollapsed(!settingsCollapsed);
                });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !editor.classList.contains('hidden')) closeEditor();
            });
            formStorage.querySelectorAll('[data-hotspot-target]').forEach(function (select) {
                select.addEventListener('change', function () {
                    const typeInput = select.form && select.form.querySelector('[name="type"]');
                    if (typeInput) typeInput.value = select.value ? 'navigation' : 'info';
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
                if (activeSceneId !== null) setButtonLabel('Viewer error: ' + message);
            });

            // If the owner switches to another scene, drop the pending marker.
            viewer.on('scenechange', function (sceneId) {
                if (activeSceneId !== null && sceneId !== 'scene-' + activeSceneId) {
                    removeMarker();
                    resetButtons();
                    activeSceneId = null;
                    activeTrigger = null;
                    setButtonLabel('The scene changed. Close this editor and reopen it for the scene you want to edit.');
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
