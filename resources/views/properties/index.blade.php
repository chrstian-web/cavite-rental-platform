@extends('layouts.public')

@section('title', 'Browse Properties')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Find Your Ideal Rental Home in Cavite</h1>
        <p class="text-slate-500">Condominiums, boarding houses, and dormitories across Cavite province.</p>
    </div>

    <!-- Layout Container -->
    <div class="lg:grid lg:grid-cols-4 lg:gap-8 items-start">

        <!-- Sidebar Filter (Desktop & Mobile Responsive) -->
        <aside class="lg:col-span-1 mb-6 lg:mb-0">
            <div class="bg-white border border-slate-200 rounded-xl p-5 sticky top-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-900">Filter Properties</h2>
                    <a href="{{ url()->current() }}" class="text-xs text-blue-600 hover:underline font-medium">Reset All</a>
                </div>

                <form method="GET" action="{{ url()->current() }}" class="space-y-4">
                    <!-- Property Type -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Property Type</label>
                        <select name="type" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All property types</option>
                            <option value="condominium" @selected(request('type') === 'condominium')>Condominium</option>
                            <option value="boarding_house" @selected(request('type') === 'boarding_house')>Boarding House</option>
                            <option value="dormitory" @selected(request('type') === 'dormitory')>Dormitory</option>
                        </select>
                    </div>

                    <!-- Location -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Location</label>
                        <select name="location_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) request('location_id') === (string) $location->id)>
                                    {{ $location->city_municipality }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Budget Range -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Monthly Rent (₱)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_rent" placeholder="Min" value="{{ request('min_rent') }}"
                                class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                            <input type="number" name="max_rent" placeholder="Max" value="{{ request('max_rent') }}"
                                class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                        </div>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Sort By</label>
                        <select name="sort" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="newest" @selected(($sort ?? request('sort')) === 'newest')>Newest</option>
                            <option value="price_low" @selected(($sort ?? request('sort')) === 'price_low')>Lowest price</option>
                            <option value="price_high" @selected(($sort ?? request('sort')) === 'price_high')>Highest price</option>
                            <option value="most_viewed" @selected(($sort ?? request('sort')) === 'most_viewed')>Most viewed</option>
                            <option value="highest_rated" @selected(($sort ?? request('sort')) === 'highest_rated')>Highest rated</option>
                        </select>
                    </div>

                    <!-- Action Button -->
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2.5 transition">
                        Apply Filters
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Property Listings Section -->
        <main class="lg:col-span-3">
            @if ($properties->isEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-12 text-center text-slate-500 shadow-sm">
                    <svg class="mx-auto h-10 w-10 text-slate-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p class="font-medium text-slate-700">No properties match your search yet.</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting or resetting your filter options.</p>
                </div>
            @else
                <!-- Tenant Property Comparison Action Bar -->
                @auth
                    @if (auth()->user()->isTenant())
                        <form id="compareForm" method="GET" action="{{ route('tenant.compare.show') }}" class="mb-4 bg-white border border-slate-200 rounded-lg p-3 flex items-center justify-between">
                            <input type="hidden" name="ids" id="compareIds">
                            <span class="text-xs text-slate-600 font-medium">Select up to 4 properties to compare:</span>
                            <button type="submit" id="compareBtn" disabled
                                class="text-xs bg-slate-200 text-slate-400 rounded-full px-4 py-1.5 cursor-not-allowed font-medium transition">
                                Compare Selected (0)
                            </button>
                        </form>
                    @endif
                @endauth

                <!-- Grid Listing -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($properties as $property)
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition relative flex flex-col justify-between">
                            <!-- Comparison Checkbox -->
                            @auth
                                @if (auth()->user()->isTenant())
                                    <label class="absolute top-2 left-2 z-10 bg-white/90 backdrop-blur rounded-md p-1 shadow border border-slate-200 cursor-pointer">
                                        <input type="checkbox" class="compare-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500" value="{{ $property->id }}">
                                    </label>
                                @endif
                            @endauth

                            <a href="{{ route('properties.show', $property->slug) }}" class="block flex-1">
                                <!-- Property Image -->
                                <div class="aspect-video bg-slate-100 flex items-center justify-center text-slate-400 text-sm relative overflow-hidden">
                                    @if ($property->images->first())
                                        <img src="{{ asset('storage/'.$property->images->first()->path) }}"
                                             alt="{{ $property->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xs text-slate-400">No photo available</span>
                                    @endif
                                </div>

                                <!-- Property Details -->
                                <div class="p-4">
                                    <span class="text-[10px] uppercase tracking-wider text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded">
                                        {{ str($property->property_type)->replace('_', ' ')->title() }}
                                    </span>

                                    <h3 class="font-semibold text-slate-900 mt-2 truncate text-base">{{ $property->name }}</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">📍 {{ $property->location->city_municipality }}, Cavite</p>

                                    <p class="text-sm font-bold text-slate-900 mt-3">
                                        @if ($property->min_monthly_rent)
                                            ₱{{ number_format($property->min_monthly_rent) }}
                                            @if ($property->max_monthly_rent && $property->max_monthly_rent != $property->min_monthly_rent)
                                                – ₱{{ number_format($property->max_monthly_rent) }}
                                            @endif
                                            <span class="text-xs font-normal text-slate-500">/ month</span>
                                        @else
                                            <span class="text-slate-500 font-normal">Contact for pricing</span>
                                        @endif
                                    </p>
                                </div>
                            </a>

                            <!-- Card Footer -->
                            <div class="px-4 pb-4 pt-0 border-t border-slate-50 mt-auto flex items-center justify-between text-xs text-slate-400">
                                <span>{{ $property->rental_spaces_count }} unit(s) listed</span>
                                <a href="{{ route('properties.show', $property->slug) }}" class="text-blue-600 hover:underline font-medium">View Details →</a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="mt-8">
                    {{ $properties->links() }}
                </div>

                <!-- Compare Checkbox Logic -->
                @auth
                    @if (auth()->user()->isTenant())
                        <script>
                            (function () {
                                const checkboxes = document.querySelectorAll('.compare-checkbox');
                                const btn = document.getElementById('compareBtn');
                                const hiddenInput = document.getElementById('compareIds');

                                function update() {
                                    const checked = Array.from(checkboxes).filter(c => c.checked);
                                    checked.slice(4).forEach(c => c.checked = false); // Cap selection to 4

                                    const active = Array.from(checkboxes).filter(c => c.checked);
                                    btn.textContent = 'Compare Selected (' + active.length + ')';
                                    hiddenInput.value = active.map(c => c.value).join(',');

                                    const enabled = active.length >= 2;
                                    btn.disabled = !enabled;
                                    btn.className = 'text-xs rounded-full px-4 py-1.5 font-medium transition ' + (enabled
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer shadow-sm'
                                        : 'bg-slate-200 text-slate-400 cursor-not-allowed');
                                }

                                checkboxes.forEach(c => c.addEventListener('change', update));
                                update();
                            })();
                        </script>
                    @endif
                @endauth
            @endif
        </main>
    </div>
@endsection
