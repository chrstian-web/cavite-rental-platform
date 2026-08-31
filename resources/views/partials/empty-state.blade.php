{{--
    Empty state block.
    Variables: $title (required), $description (optional), $actionUrl, $actionLabel (optional pair)
--}}
<div class="bg-white border border-slate-200 rounded-2xl p-10 sm:p-14 text-center shadow-sm">
    <div class="mx-auto w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
    </div>
    <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
    @if (!empty($description))
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if (!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}"
           class="inline-flex items-center justify-center mt-6 min-h-[44px] px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ $actionLabel }}
        </a>
    @endif
</div>
