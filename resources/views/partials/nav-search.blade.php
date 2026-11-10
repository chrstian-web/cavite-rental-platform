@php
    $navSearchButtonClass = $navSearchButtonClass ?? 'list-none cursor-pointer flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 select-none [&::-webkit-details-marker]:hidden';
    $navSearchLabelClass = $navSearchLabelClass ?? 'hidden md:inline';
@endphp
<details class="relative group">
    <summary title="Search properties"
        class="{{ $navSearchButtonClass }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
        </svg>
        <span class="{{ $navSearchLabelClass }}">Search</span>
    </summary>

    <div class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-lg z-50 p-4">
        <form method="GET" action="{{ route('properties.index') }}" class="space-y-2">
            <label class="sr-only" for="nav-search-keyword">Search properties</label>
            <input id="nav-search-keyword" type="search" name="q" value="{{ request('q') }}"
                placeholder="Search by name or city" autocomplete="off"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <select name="location_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">Any location</option>
                @foreach (\Illuminate\Support\Facades\Schema::hasTable('locations') ? \App\Models\Location::where('is_active', true)->orderBy('city_municipality')->get() : collect() as $loc)
                    <option value="{{ $loc->id }}" @selected((string) request('location_id') === (string) $loc->id)>{{ $loc->city_municipality }}</option>
                @endforeach
            </select>
            <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">Any property type</option>
                <option value="condominium" @selected(request('type') === 'condominium')>Condominium</option>
                <option value="boarding_house" @selected(request('type') === 'boarding_house')>Boarding House</option>
                <option value="dormitory" @selected(request('type') === 'dormitory')>Dormitory</option>
            </select>
            <div class="grid grid-cols-2 gap-2">
                <input type="number" name="min_rent" placeholder="Min ₱" value="{{ request('min_rent') }}" class="rounded-lg border-slate-300 text-sm">
                <input type="number" name="max_rent" placeholder="Max ₱" value="{{ request('max_rent') }}" class="rounded-lg border-slate-300 text-sm">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2">
                Search Properties
            </button>
            <a href="{{ route('properties.index') }}" class="block text-center text-xs text-slate-400 hover:text-slate-600 pt-1">
                Browse all properties &rarr;
            </a>
        </form>
    </div>
</details>
