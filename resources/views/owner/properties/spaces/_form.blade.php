{{-- Shared create/edit form for a rental space. $space is null on create. --}}
@php($space = $space ?? null)
<div x-data="{ propertyType: '{{ $property->property_type }}' }">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Unit / Room number</label>
            <input type="text" name="space_number" value="{{ old('space_number', $space->space_number ?? '') }}" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Type label (e.g. Studio, Single, Shared)</label>
            <input type="text" name="space_type" value="{{ old('space_type', $space->space_type ?? '') }}"
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bedrooms</label>
            <input type="number" min="0" name="bedrooms" value="{{ old('bedrooms', $space->bedrooms ?? 0) }}" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bathrooms</label>
            <input type="number" min="0" name="bathrooms" value="{{ old('bathrooms', $space->bathrooms ?? 0) }}" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Total capacity</label>
            <input type="number" min="1" name="total_capacity" value="{{ old('total_capacity', $space->total_capacity ?? 1) }}" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Floor area (sqm)</label>
            <input type="number" step="0.01" min="0" name="floor_area_sqm" value="{{ old('floor_area_sqm', $space->floor_area_sqm ?? '') }}"
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Monthly rent (₱)</label>
            <input type="number" step="0.01" min="0" name="monthly_rent" value="{{ old('monthly_rent', $space->monthly_rent ?? '') }}" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Security deposit (₱)</label>
            <input type="number" step="0.01" min="0" name="security_deposit" value="{{ old('security_deposit', $space->security_deposit ?? 0) }}"
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Advance payment (₱)</label>
            <input type="number" step="0.01" min="0" name="advance_payment" value="{{ old('advance_payment', $space->advance_payment ?? 0) }}"
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                @foreach (['available', 'occupied', 'reserved', 'maintenance', 'inactive'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $space->status ?? 'available') === $status)>{{ str($status)->title() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_furnished" value="1" @checked(old('is_furnished', $space->is_furnished ?? false)) class="rounded border-slate-300">
                Furnished
            </label>
        </div>
    </div>

    {{-- Type-specific fields, shown/hidden with Alpine based on the property's type --}}
    <div class="mt-4 pt-4 border-t border-slate-100">
        <p class="text-sm font-medium text-slate-700 mb-3">Type-specific details</p>

        <div x-show="propertyType === 'condominium'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Floor</label>
                <input type="text" name="attributes[floor]" value="{{ old('attributes.floor', $space->attributes['floor'] ?? '') }}"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Unit type</label>
                <input type="text" name="attributes[unit_type]" value="{{ old('attributes.unit_type', $space->attributes['unit_type'] ?? '') }}"
                    placeholder="e.g. Studio, 1-Bedroom" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div x-show="propertyType === 'boarding_house'">
            <label class="block text-sm font-medium text-slate-700 mb-1">Room type</label>
            <input type="text" name="attributes[room_type]" value="{{ old('attributes.room_type', $space->attributes['room_type'] ?? '') }}"
                placeholder="e.g. Solo, Shared (2 pax)" class="w-full md:w-64 rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div x-show="propertyType === 'dormitory'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Gender restriction</label>
                <select name="attributes[gender_restriction]" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">None</option>
                    @foreach (['male' => 'Male only', 'female' => 'Female only', 'mixed' => 'Mixed'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('attributes.gender_restriction', $space->attributes['gender_restriction'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Curfew time</label>
                <input type="time" name="attributes[curfew_time]" value="{{ old('attributes.curfew_time', $space->attributes['curfew_time'] ?? '') }}"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">Utilities included</label>
        @php($selectedUtilities = old('utilities_included', $space->utilities_included ?? []) ?? [])
        <div class="flex flex-wrap gap-4">
            @foreach (['water' => 'Water', 'electricity' => 'Electricity', 'wifi' => 'Wifi', 'cable' => 'Cable TV'] as $value => $label)
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="utilities_included[]" value="{{ $value }}"
                        @checked(in_array($value, $selectedUtilities)) class="rounded border-slate-300">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-4 pt-4 border-t border-slate-100">
        <label class="block text-sm font-medium text-slate-700 mb-1">Photos of this specific unit/room</label>
        <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm">
        <p class="text-xs text-slate-400 mt-1">
            Up to 8 images, 5MB each. Renters see these on the property page so they know exactly what this
            unit looks like, even if they can't visit in person.
        </p>

        @if (isset($space) && $space->images->isNotEmpty())
            <div class="grid grid-cols-4 gap-2 mt-3">
                @foreach ($space->images as $image)
                    <div class="relative group">
                        <img src="{{ asset('storage/'.$image->path) }}" class="w-full h-20 object-cover rounded-lg
                            {{ $image->is_cover ? 'ring-2 ring-blue-500' : '' }}">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                            @unless ($image->is_cover)
                                <form action="{{ route('owner.properties.spaces.images.cover', [$property, $space, $image]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button class="text-white text-xs underline">Set cover</button>
                                </form>
                            @endunless
                            <form action="{{ route('owner.properties.spaces.images.destroy', [$property, $space, $image]) }}" method="POST"
                                  onsubmit="return confirm('Remove this photo?')">
                                @csrf @method('DELETE')
                                <button class="text-white text-xs underline">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
