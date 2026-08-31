{{-- A nav link with a small red "light" above it when there's an unread
     notification relevant to that section. Usage: @include('partials.nav-link', ['route' => '...', 'label' => '...', 'notifyTypes' => 'type_or_array']) --}}
<a href="{{ route($route) }}" class="relative text-sm text-slate-600 hover:text-slate-900">
    @if (auth()->user()->hasUnreadNotificationOfType($notifyTypes))
        <span class="absolute -top-1.5 -right-2 w-2 h-2 bg-red-500 rounded-full"></span>
    @endif
    {{ $label }}
</a>
