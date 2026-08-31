@extends('layouts.app')

@section('title', 'Recommended for You')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Get personalized recommendations</h1>
    <p class="text-sm text-slate-500 mb-6">
        Tell us what you're looking for, and we'll score every available unit against your preferences —
        with a transparent breakdown of why each one was recommended.
    </p>

    <form method="POST" action="{{ route('tenant.recommendations.store') }}" class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 max-w-2xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Maximum monthly budget (₱)</label>
                <input type="number" step="0.01" name="max_budget" placeholder="e.g. 10000"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Preferred city/municipality</label>
                <select name="location_id" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">No preference</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->city_municipality }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Property type</label>
                <select name="property_type" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">No preference</option>
                    <option value="condominium">Condominium</option>
                    <option value="boarding_house">Boarding House</option>
                    <option value="dormitory">Dormitory</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Furnishing</label>
                <select name="furnishing" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">No preference</option>
                    <option value="furnished">Furnished</option>
                    <option value="unfurnished">Unfurnished</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Number of occupants</label>
                <input type="number" min="1" name="number_of_occupants" placeholder="e.g. 2"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Required bedrooms</label>
                <input type="number" min="0" name="required_bedrooms" placeholder="e.g. 1"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Required amenities</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach ($amenities as $amenity)
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="amenity_ids[]" value="{{ $amenity->id }}" class="rounded border-slate-300">
                        {{ $amenity->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <details class="pt-2">
            <summary class="text-sm text-blue-600 cursor-pointer">Distance preference (optional, advanced)</summary>
            <p class="text-xs text-slate-400 mt-2 mb-2">
                If you know the coordinates of your workplace/school, we can score properties by proximity to it.
            </p>
            <div class="grid grid-cols-3 gap-3">
                <input type="number" step="0.0000001" name="reference_latitude" placeholder="Latitude" class="rounded-lg border-slate-300 text-sm">
                <input type="number" step="0.0000001" name="reference_longitude" placeholder="Longitude" class="rounded-lg border-slate-300 text-sm">
                <input type="number" step="0.1" name="max_distance_km" placeholder="Max distance (km)" class="rounded-lg border-slate-300 text-sm">
            </div>
        </details>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Get recommendations
        </button>
    </form>
@endsection
