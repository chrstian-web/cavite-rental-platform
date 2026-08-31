@extends('layouts.app')

@section('title', 'Compare Properties')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Compare Properties</h1>

    @if ($properties->count() < 2)
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            Select at least 2 properties from the listings page to compare.
            <a href="{{ route('properties.index') }}" class="text-blue-600 hover:underline block mt-2">Browse listings</a>
        </div>
    @else
        @php
            $maxRent = $properties->max(fn ($p) => $p->min_monthly_rent);
            $bestValueId = $properties->sortBy('min_monthly_rent')->first()->id;
        @endphp
        <div class="overflow-x-auto bg-white border border-slate-200 rounded-xl">
            <table class="w-full text-sm min-w-[600px]">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="p-4 text-left text-slate-400 font-normal w-40">Criteria</th>
                        @foreach ($properties as $property)
                            <th class="p-4 text-left">
                                <a href="{{ route('properties.show', $property->slug) }}" class="font-semibold text-slate-900 hover:underline">
                                    {{ $property->name }}
                                </a>
                                @if ($property->id === $bestValueId)
                                    <span class="block text-xs text-green-600 mt-1">✓ Best price here</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="p-4 text-slate-500">Monthly Rent</td>
                        @foreach ($properties as $property)
                            <td class="p-4 font-medium {{ $property->id === $bestValueId ? 'text-green-600' : 'text-slate-900' }}">
                                ₱{{ number_format($property->min_monthly_rent ?? 0) }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Property Type</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">{{ str($property->property_type)->replace('_',' ')->title() }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Location</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">{{ $property->location->city_municipality }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Bedrooms (range)</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">{{ $property->rentalSpaces->pluck('bedrooms')->min() ?? '—' }}–{{ $property->rentalSpaces->pluck('bedrooms')->max() ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Bathrooms (range)</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">{{ $property->rentalSpaces->pluck('bathrooms')->min() ?? '—' }}–{{ $property->rentalSpaces->pluck('bathrooms')->max() ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Capacity (range)</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">{{ $property->rentalSpaces->pluck('total_capacity')->min() ?? '—' }}–{{ $property->rentalSpaces->pluck('total_capacity')->max() ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Amenities</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900 text-xs">
                                {{ $property->amenities->pluck('name')->join(', ') ?: '—' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Furnished units</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">{{ $property->rentalSpaces->where('is_furnished', true)->count() }} of {{ $property->rentalSpaces->count() }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500">Rating</td>
                        @foreach ($properties as $property)
                            <td class="p-4 text-slate-900">
                                {{ $property->reviews_avg_rating ? number_format($property->reviews_avg_rating, 1).' ★' : 'No reviews yet' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="p-4 text-slate-500"></td>
                        @foreach ($properties as $property)
                            <td class="p-4">
                                <a href="{{ route('properties.show', $property->slug) }}" class="text-blue-600 hover:underline">View details</a>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
