{{--
    Reusable property listing card.
    Variables:
      $property (required)
      $showCompare (bool, default false) — renders compare-checkbox
      $showRemoveFavorite (bool, default false) — renders remove-from-favorites form
--}}
@php
    $showCompare = $showCompare ?? false;
    $showRemoveFavorite = $showRemoveFavorite ?? false;
@endphp

<article class="group bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition-all duration-200 relative">
    @if ($showCompare)
        <label class="absolute top-3 left-3 z-10 flex items-center gap-1.5 bg-white/95 backdrop-blur-sm rounded-lg px-2.5 py-1.5 shadow-sm border border-slate-200 cursor-pointer min-h-[44px]">
            <input type="checkbox" class="compare-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" value="{{ $property->id }}">
            <span class="text-xs font-medium text-slate-600">Compare</span>
        </label>
    @endif

    <a href="{{ route('properties.show', $property->slug) }}" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 rounded-2xl">
        <div class="aspect-[4/3] bg-slate-100 relative overflow-hidden">
            @if ($property->images->first())
                <img src="{{ asset('storage/'.$property->images->first()->path) }}"
                     alt="{{ $property->name }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-2">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm">No photo yet</span>
                </div>
            @endif
            <span class="absolute top-3 right-3 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold uppercase tracking-wide bg-white/95 backdrop-blur-sm text-blue-700 border border-blue-100">
                {{ str($property->property_type)->replace('_', ' ')->title() }}
            </span>
        </div>

        <div class="p-4 sm:p-5">
            <h3 class="font-semibold text-slate-900 text-base leading-snug group-hover:text-blue-600 transition-colors">{{ $property->name }}</h3>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $property->location->city_municipality }}, Cavite
            </p>

            <div class="mt-3 flex items-end justify-between gap-2">
                <p class="text-base font-bold text-slate-900">
                    @if ($property->min_monthly_rent)
                        ₱{{ number_format($property->min_monthly_rent) }}
                        @if ($property->max_monthly_rent && $property->max_monthly_rent != $property->min_monthly_rent)
                            <span class="text-sm font-normal text-slate-500">– ₱{{ number_format($property->max_monthly_rent) }}</span>
                        @endif
                        <span class="text-sm font-normal text-slate-500">/mo</span>
                    @else
                        <span class="text-sm font-medium text-slate-600">Contact for pricing</span>
                    @endif
                </p>
                @if ($property->reviews_count)
                    <span class="inline-flex items-center gap-0.5 text-sm text-slate-600 shrink-0">
                        <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ number_format($property->reviews_avg_rating, 1) }}
                    </span>
                @endif
            </div>

            <p class="text-xs text-slate-400 mt-2">{{ $property->rental_spaces_count }} unit{{ $property->rental_spaces_count === 1 ? '' : 's' }} listed</p>
        </div>
    </a>

    @if ($showRemoveFavorite)
        <div class="px-4 sm:px-5 pb-4 sm:pb-5 -mt-1">
            <form method="POST" action="{{ route('tenant.favorites.toggle', $property) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 text-sm text-red-600 hover:text-red-700 font-medium min-h-[44px] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 rounded-lg px-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                    </svg>
                    Remove from favorites
                </button>
            </form>
        </div>
    @endif
</article>
