{{-- Shared create/edit form. $property is null on create. --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Property name</label>
        <input type="text" name="name" value="{{ old('name', $property->name ?? '') }}" required
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Property type</label>
        <select name="property_type" required class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @foreach (['condominium' => 'Condominium', 'boarding_house' => 'Boarding House', 'dormitory' => 'Dormitory'] as $value => $label)
                <option value="{{ $value }}" @selected(old('property_type', $property->property_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
    <textarea name="description" rows="4"
        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $property->description ?? '') }}</textarea>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">City / Municipality</label>
        <select name="location_id" required class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select location</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected((string) old('location_id', $property->location_id ?? '') === (string) $location->id)>
                    {{ $location->city_municipality }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Street address</label>
        <input type="text" name="address_line" value="{{ old('address_line', $property->address_line ?? '') }}" required
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Min. monthly rent (₱)</label>
        <input type="number" step="0.01" name="min_monthly_rent" value="{{ old('min_monthly_rent', $property->min_monthly_rent ?? '') }}"
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Max. monthly rent (₱)</label>
        <input type="number" step="0.01" name="max_monthly_rent" value="{{ old('max_monthly_rent', $property->max_monthly_rent ?? '') }}"
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Contact person</label>
        <input type="text" name="contact_person" value="{{ old('contact_person', $property->contact_person ?? '') }}"
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Contact number</label>
        <input type="text" name="contact_number" value="{{ old('contact_number', $property->contact_number ?? '') }}"
            inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="09171234567"
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-slate-400 mt-1">11 digits, numbers only</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Contact email</label>
        <input type="email" name="contact_email" value="{{ old('contact_email', $property->contact_email ?? '') }}"
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
    </div>
</div>

@if (isset($property))
    <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Availability status</label>
        <select name="availability_status" class="w-full md:w-64 rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @foreach (['available', 'fully_booked', 'unavailable', 'under_review'] as $status)
                <option value="{{ $status }}" @selected(old('availability_status', $property->availability_status) === $status)>
                    {{ str($status)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
    </div>
@endif

<div class="mt-4">
    <label class="block text-sm font-medium text-slate-700 mb-2">Amenities</label>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        @php $selectedAmenities = old('amenities', isset($property) ? $property->amenities->pluck('id')->all() : []); @endphp
        @foreach ($amenities as $amenity)
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                    @checked(in_array($amenity->id, $selectedAmenities)) class="rounded border-slate-300">
                {{ $amenity->name }}
            </label>
        @endforeach
    </div>
</div>

<div class="mt-4">
    <label class="block text-sm font-medium text-slate-700 mb-1">Photos</label>
    <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm">
    <p class="text-xs text-slate-400 mt-1">Up to 10 images, 5MB each. First upload becomes the cover photo automatically.</p>

    @if (isset($property) && $property->images->isNotEmpty())
        <div class="grid grid-cols-4 gap-2 mt-3">
            @foreach ($property->images as $image)
                <div class="relative group">
                    <img src="{{ asset('storage/'.$image->path) }}" class="w-full h-20 object-cover rounded-lg
                        {{ $image->is_cover ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                        @unless ($image->is_cover)
                            <form action="{{ route('owner.properties.images.cover', [$property, $image]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="text-white text-xs underline">Set cover</button>
                            </form>
                        @endunless
                        <form action="{{ route('owner.properties.images.destroy', [$property, $image]) }}" method="POST"
                              onsubmit="return confirm('Remove this image?')">
                            @csrf @method('DELETE')
                            <button class="text-white text-xs underline">Remove</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
