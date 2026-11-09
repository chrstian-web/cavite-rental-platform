@if ($property->images->isNotEmpty())
    <section class="mt-5 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="current-photos-heading">
        <h2 id="current-photos-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Current photos</h2>
        <p class="mb-3 text-xs text-slate-500">Manage saved photos here. These actions are separate from saving property details.</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($property->images as $image)
                <div class="relative group">
                    @if ($image->is_cover)
                        <span class="absolute left-2 top-2 z-10 rounded bg-blue-600 px-2 py-1 text-[10px] font-semibold text-white">Cover photo</span>
                    @endif
                    <img src="{{ asset('storage/'.$image->path) }}" alt="Photo of {{ $property->name }}" class="w-full h-24 rounded-lg object-cover {{ $image->is_cover ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="absolute inset-0 flex items-center justify-center gap-2 rounded-lg bg-black/40 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100">
                        @unless ($image->is_cover)
                            <form action="{{ route('owner.properties.images.cover', [$property, $image]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded bg-white px-2 py-1 text-xs font-medium text-slate-900">Set cover</button>
                            </form>
                        @endunless
                        <form action="{{ route('owner.properties.images.update', [$property, $image]) }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PATCH')
                            <label class="inline-flex cursor-pointer items-center justify-center rounded bg-white px-2 py-1 text-xs font-medium text-slate-900">
                                Edit
                                <input type="file" name="image" accept="image/*" class="sr-only" required onchange="this.form.submit()">
                            </label>
                        </form>
                        <form action="{{ route('owner.properties.images.destroy', [$property, $image]) }}" method="POST"
                              onsubmit="return confirm('Remove this image?')">
                            @csrf
                            @method('DELETE')
                            <x-delete-button label="Remove" size="sm" />
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
