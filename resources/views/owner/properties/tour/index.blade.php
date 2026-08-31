@extends('layouts.app')

@section('title', $property->name.' — Virtual Tour')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
@endpush

@section('content')
    <a href="{{ route('owner.properties.index') }}" class="text-sm text-blue-600 hover:underline">&larr; My Properties</a>
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
                                        Tip: for a floor-level arrow (like Street View), use a Pitch around
                                        <strong>-40 to -60</strong>. Yaw 0 points forward from where the photo was taken;
                                        try -90/90 for left/right, 180 for behind.
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
                                        <input type="number" step="0.01" name="position_x" placeholder="Yaw (-180 to 180)" required
                                            class="rounded-lg border-slate-300 text-xs">
                                        <input type="number" step="0.01" name="position_y" placeholder="Pitch, e.g. -50 for floor" required
                                            class="rounded-lg border-slate-300 text-xs">
                                        <button type="submit" class="col-span-2 bg-slate-800 hover:bg-slate-900 text-white text-xs rounded-lg py-1.5">
                                            Add hotspot
                                        </button>
                                    </form>
                                </details>

                                @if ($scene->hotspots->isNotEmpty())
                                    <ul class="mt-2 text-xs text-slate-500 space-y-1">
                                        @foreach ($scene->hotspots as $hotspot)
                                            <li class="flex items-center justify-between">
                                                <span>{{ $hotspot->label ?? 'Hotspot' }} (yaw {{ $hotspot->position_x }}, pitch {{ $hotspot->position_y }})</span>
                                                <form action="{{ route('owner.properties.tour.hotspots.destroy', [$property, $tour, $scene, $hotspot]) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button class="text-red-500 hover:underline">remove</button>
                                                </form>
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
