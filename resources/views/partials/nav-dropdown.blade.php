{{-- Generic labeled dropdown for grouping related nav links.
     Usage: @include('partials.nav-dropdown', ['label' => 'Rentals', 'notifyTypes' => [...] optional])
            then push <a> links into the $slot via @section/@include pattern below,
            OR simply wrap with @component-style — here we accept a $links array of ['route'=>, 'label'=>, 'notifyTypes'=>optional] --}}
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.outside="open = false"
        class="relative flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900">
        @if (collect($links)->contains(fn ($l) => isset($l['notifyTypes']) && auth()->user()->hasUnreadNotificationOfType($l['notifyTypes'])))
            <span class="absolute -top-1.5 -right-2 w-2 h-2 bg-red-500 rounded-full"></span>
        @endif
        {{ $label }}
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-cloak style="display:none" x-transition
         class="absolute left-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg z-50 py-1">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" class="relative flex items-center justify-between px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                {{ $link['label'] }}
                @if (isset($link['notifyTypes']) && auth()->user()->hasUnreadNotificationOfType($link['notifyTypes']))
                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                @endif
            </a>
        @endforeach
    </div>
</div>
