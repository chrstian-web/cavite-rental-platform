@extends('layouts.public')

@section('title', $property->name)

@push('head')
    @if ($property->virtualTour && $property->virtualTour->status === 'published')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
        <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    @endif
@endpush

@section('content')
    <a href="{{ route('properties.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to listings</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-4">
        <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-2 gap-2">
                @forelse ($property->images as $image)
                    <div class="{{ $loop->first ? 'col-span-2 aspect-video' : 'aspect-square' }} bg-slate-100 rounded-xl overflow-hidden">
                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $property->name }}" class="w-full h-full object-cover">
                    </div>
                @empty
                    <div class="col-span-2 aspect-video bg-slate-100 rounded-xl flex items-center justify-center text-slate-400">
                        No photos uploaded yet
                    </div>
                @endforelse
            </div>

            @if ($property->virtualTour && $property->virtualTour->status === 'published' && $property->virtualTour->scenes->isNotEmpty())
                <div>
                    <h2 class="font-semibold text-slate-900 mb-2">Virtual Tour</h2>
                    @include('partials.virtual-tour-viewer', ['tour' => $property->virtualTour, 'viewerId' => 'publicTour'])
                </div>
            @endif

            <div>
                <span class="text-xs uppercase tracking-wide text-blue-600 font-medium">
                    {{ str($property->property_type)->replace('_', ' ')->title() }}
                </span>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $property->name }}</h1>
                <p class="text-slate-500">{{ $property->address_line }}, {{ $property->barangay?->name }} {{ $property->location->city_municipality }}, Cavite</p>
                @if ($property->reviews_count)
                    <p class="text-sm text-slate-500 mt-1">★ {{ number_format($property->reviews_avg_rating, 1) }} ({{ $property->reviews_count }} review{{ $property->reviews_count === 1 ? '' : 's' }})</p>
                @endif
            </div>

            @if ($property->description)
                <div>
                    <h2 class="font-semibold text-slate-900 mb-2">About this property</h2>
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ $property->description }}</p>
                </div>
            @endif

            @if ($property->amenities->isNotEmpty())
                <div>
                    <h2 class="font-semibold text-slate-900 mb-2">Amenities</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($property->amenities as $amenity)
                            <span class="text-xs bg-slate-100 text-slate-700 rounded-full px-3 py-1">{{ $amenity->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($property->house_rules))
                <div>
                    <h2 class="font-semibold text-slate-900 mb-2">House rules</h2>
                    <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                        @foreach ($property->house_rules as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <h2 class="font-semibold text-slate-900 mb-2">Available units/rooms</h2>
                @if ($property->rentalSpaces->isEmpty())
                    <p class="text-sm text-slate-500">No units listed yet.</p>
                @else
                    <div class="border border-slate-200 rounded-xl divide-y divide-slate-200">
                        @foreach ($property->rentalSpaces as $space)
                            <div class="p-4 flex items-center gap-4 text-sm">
                                @php $spaceCover = $space->images->firstWhere('is_cover', true) ?? $space->images->first(); @endphp
                                <div class="w-20 h-16 rounded-lg bg-slate-100 shrink-0 overflow-hidden flex items-center justify-center text-slate-300 text-[10px]">
                                    @if ($spaceCover)
                                        <img src="{{ asset('storage/'.$spaceCover->path) }}" alt="{{ $space->space_number }}" class="w-full h-full object-cover">
                                    @else
                                        No photo
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-slate-900">{{ $space->space_number }} &middot; {{ $space->space_type ?? '—' }}</p>
                                    <p class="text-slate-500">{{ $space->bedrooms }} bed &middot; {{ $space->bathrooms }} bath &middot; capacity {{ $space->total_capacity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-slate-900">₱{{ number_format($space->monthly_rent) }}/mo</p>
                                    <span class="text-xs {{ $space->status === 'available' ? 'text-green-600' : 'text-slate-400' }}">
                                        {{ str($space->status)->title() }}
                                    </span>
                                    @auth
                                        @if (auth()->user()->isTenant() && $space->status === 'available')
                                            <a href="{{ route('tenant.applications.create', $space) }}" class="block text-xs text-blue-600 hover:underline mt-1">Apply</a>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 sticky top-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Monthly rent</p>
                        <p class="text-xl font-bold text-slate-900">
                            @if ($property->min_monthly_rent)
                                ₱{{ number_format($property->min_monthly_rent) }}
                                @if ($property->max_monthly_rent && $property->max_monthly_rent != $property->min_monthly_rent)
                                    – ₱{{ number_format($property->max_monthly_rent) }}
                                @endif
                            @else
                                Contact for pricing
                            @endif
                        </p>
                    </div>
                    @auth
                        @if (auth()->user()->isTenant())
                            @php $isFavorited = auth()->user()->favorites()->where('property_id', $property->id)->exists(); @endphp
                            <form method="POST" action="{{ route('tenant.favorites.toggle', $property) }}">
                                @csrf
                                <button type="submit" title="{{ $isFavorited ? 'Remove from favorites' : 'Add to favorites' }}"
                                    class="text-xl {{ $isFavorited ? 'text-red-500' : 'text-slate-300 hover:text-red-400' }}">
                                    {{ $isFavorited ? '♥' : '♡' }}
                                </button>
                            </form>
                        @endif
                    @endauth
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-600 space-y-1">
                    <p class="font-medium text-slate-900">Contact</p>
                    <p>{{ $property->contact_person ?? $property->owner->first_name.' '.$property->owner->last_name }}</p>
                    @if ($property->contact_number)<p>{{ $property->contact_number }}</p>@endif
                    @if ($property->contact_email)<p>{{ $property->contact_email }}</p>@endif
                </div>

                @auth
                    @if (auth()->user()->isTenant())
                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                            <a href="{{ route('tenant.viewings.create', $property) }}"
                               class="block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg py-2.5">
                                Request a Viewing
                            </a>
                            @if ($property->rentalSpaces->where('status', 'available')->isNotEmpty())
                                <p class="text-xs text-slate-400 text-center pt-1">
                                    To apply, pick a unit/room above and use its Apply link.
                                </p>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('login') }}" class="block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg py-2.5">
                            Log in to apply or request a viewing
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
@endsection
