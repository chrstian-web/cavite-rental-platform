@php
    $recentNotifications = auth()->user()->notifications()->take(8)->get();
    $unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.outside="open = false" class="relative text-slate-500 hover:text-slate-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] leading-none rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak style="display:none" class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-lg z-50">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-medium text-slate-900">Notifications</p>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    @method('PATCH')
                    <button class="text-xs text-blue-600 hover:underline">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
            @forelse ($recentNotifications as $notification)
                @include('partials.notification-item', ['notification' => $notification])
            @empty
                <p class="px-4 py-6 text-sm text-slate-400 text-center">No notifications yet.</p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" class="block text-center text-xs text-blue-600 hover:underline px-4 py-3 border-t border-slate-100">
            View all notifications
        </a>
    </div>
</div>
