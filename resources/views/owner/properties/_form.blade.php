{{-- Shared create/edit form. $property is null on create. --}}

<section aria-labelledby="property-details-heading">
<h2 id="property-details-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Property details</h2>
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
    <label for="property-terms-and-conditions" class="block text-sm font-medium text-slate-700 mb-1">Terms and Conditions</label>
    <textarea id="property-terms-and-conditions" name="description" rows="4"
        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $property->description ?? '') }}</textarea>
    <p class="mt-1 text-xs text-slate-500">These terms will be shown to tenants on each room or unit details page.</p>
</div>
</section>

<section class="mt-5" aria-labelledby="property-address-heading">
<h2 id="property-address-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Location</h2>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">City / Municipality</label>
        <select name="location_id" required class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select location</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}"
                    @selected((string) old('location_id', $property->location_id ?? '') === (string) $location->id)>
                    {{ $location->city_municipality }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="address_line" class="block text-sm font-medium text-slate-700 mb-1">Street address</label>
        <input id="address_line" type="text" name="address_line" value="{{ old('address_line', $property->address_line ?? '') }}" required autocomplete="street-address" placeholder="e.g. 123 Rizal Street, Brgy. San Agustin"
            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        <p class="mt-1 text-xs text-slate-500">Include the street, barangay, or a nearby landmark.</p>
    </div>
</div>
    <input type="hidden" name="latitude" value="{{ old('latitude', $property->latitude ?? '') }}">
    <input type="hidden" name="longitude" value="{{ old('longitude', $property->longitude ?? '') }}">
</section>

<section class="mt-5" aria-labelledby="monthly-rent-heading">
<h2 id="monthly-rent-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Monthly rent</h2>
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
</section>

<section class="mt-5" aria-labelledby="contact-information-heading">
<h2 id="contact-information-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Contact information</h2>
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
</section>

@if (isset($property))
    <section class="mt-5" aria-labelledby="listing-status-heading">
        <h2 id="listing-status-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Listing status</h2>
        <label class="block text-sm font-medium text-slate-700 mb-1">Availability status</label>
        <select name="availability_status" class="w-full md:w-64 rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @foreach (['available', 'fully_booked', 'unavailable', 'under_review'] as $status)
                <option value="{{ $status }}" @selected(old('availability_status', $property->availability_status) === $status)>
                    {{ str($status)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
    </section>
@endif

<section class="mt-5" aria-labelledby="amenities-heading">
    <h2 id="amenities-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Amenities</h2>
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
</section>

<section class="mt-5" aria-labelledby="property-photos-heading">
    <h2 id="property-photos-heading" class="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-900">Photos</h2>
    <input id="property-image-input" type="file" name="images[]" multiple accept="image/*"
           data-has-cover="{{ isset($property) && $property->images->contains('is_cover', true) ? 'true' : 'false' }}"
           class="w-full text-sm">
    <p class="mt-1 text-xs text-slate-500">Up to 10 images per upload, 5MB each. The first upload becomes the cover if the property does not already have one.</p>
    <div id="property-image-previews" class="mt-3 hidden grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Selected photo previews"></div>
    <p id="property-image-status" class="mt-2 text-xs text-slate-500" role="status" aria-live="polite">Choose photos to preview them before saving.</p>
</section>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('property-image-input');
            const preview = document.getElementById('property-image-previews');
            const status = document.getElementById('property-image-status');
            if (!input || !preview || !status) return;

            const hasCover = input.dataset.hasCover === 'true';
            let previewUrls = [];

            input.addEventListener('change', function () {
                previewUrls.forEach((url) => URL.revokeObjectURL(url));
                previewUrls = [];
                preview.replaceChildren();

                const files = Array.from(input.files || []);
                if (!files.length) {
                    preview.classList.add('hidden');
                    preview.classList.remove('grid');
                    status.textContent = 'Choose photos to preview them before saving.';
                    return;
                }

                preview.classList.remove('hidden');
                preview.classList.add('grid');

                files.forEach(function (file, index) {
                    if (!file.type.startsWith('image/')) return;

                    const card = document.createElement('figure');
                    card.className = 'overflow-hidden rounded-lg border border-slate-200 bg-white';

                    const image = document.createElement('img');
                    const url = URL.createObjectURL(file);
                    previewUrls.push(url);
                    image.src = url;
                    image.alt = 'Preview of ' + file.name;
                    image.className = 'h-24 w-full object-cover';

                    const caption = document.createElement('figcaption');
                    caption.className = 'px-2 py-1.5 text-xs font-medium text-slate-700';
                    caption.textContent = !hasCover && index === 0 ? 'Will be the cover' : 'New photo';

                    card.append(image, caption);
                    preview.appendChild(card);
                });

                let message = files.length + (files.length === 1 ? ' photo selected.' : ' photos selected.');
                message += hasCover
                    ? ' The current cover photo will stay unchanged.'
                    : ' The first selected photo will be the cover.';
                if (files.length > 10) message += ' The maximum is 10 photos per upload.';
                status.textContent = message;
            });

            window.addEventListener('pagehide', function () {
                previewUrls.forEach((url) => URL.revokeObjectURL(url));
            }, { once: true });
        });
    </script>
@endpush
