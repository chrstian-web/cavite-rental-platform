@if ($space->images->isNotEmpty())
    <div class="mt-4 bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-slate-900">Existing unit photos</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3">
            @foreach ($space->images as $image)
                <div class="relative group">
                    <img src="{{ asset('storage/'.$image->path) }}" class="w-full h-20 object-cover rounded-lg {{ $image->is_cover ? 'ring-2 ring-blue-500' : '' }}" alt="Unit photo">
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/55 p-2 transition sm:opacity-0 sm:group-hover:opacity-100">
                        @unless ($image->is_cover)
                            <form action="{{ route('owner.properties.spaces.images.cover', [$property, $space, $image]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/30 bg-white/15 px-3 py-2 text-xs font-semibold text-white shadow-sm backdrop-blur transition hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Set cover</button>
                            </form>
                        @endunless
                        <form action="{{ route('owner.properties.spaces.images.destroy', [$property, $space, $image]) }}" method="POST" onsubmit="return confirm('Remove this photo?')">
                            @csrf @method('DELETE')
                            <x-delete-button label="Remove" size="sm" />
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
