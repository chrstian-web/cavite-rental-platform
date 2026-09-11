<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cavite Rental Platform')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 flex flex-col" data-role="tenant">

    <!-- Top Navigation Bar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

            <!-- Logo & Left Links -->
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="font-extrabold tracking-tight text-lg text-slate-900 flex items-center gap-1">
                    Cavite<span class="text-blue-600">Rentals</span>
                </a>

                <!-- Role-Based Main Navigation -->
                <div class="hidden lg:flex items-center gap-5 text-sm font-semibold text-slate-600">
                    <a href="{{ route('home') }}" class="hover:text-blue-600 transition">Home</a>
                    <a href="{{ route('properties.index') }}" class="hover:text-blue-600 transition">Properties</a>

                    @auth
                        @if (auth()->user()->isTenant())
                            <a href="{{ route('tenant.recommendations.create') }}" class="hover:text-blue-600 transition">Recommendations</a>
                        @elseif (auth()->user()->isOwner())
                            <a href="{{ route('owner.properties.index') }}" class="hover:text-blue-600 transition">My Properties</a>
                            <a href="{{ route('owner.applications.index') }}" class="hover:text-blue-600 transition">Applications</a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Search Component Bar -->
            <div class="flex-1 max-w-xs hidden sm:block">
                @include('partials.nav-search')
            </div>

            <!-- Right Nav Actions (Notifications & Profile Menu) -->
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <!-- Notifications Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="relative p-2 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>

                            <!-- Unread Indicator Badge -->
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
                            @endif
                        </button>

                        <!-- Notification Dropdown Menu -->
                        <div x-show="open" x-cloak x-transition
                             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                                <span class="font-semibold text-slate-900">Notifications</span>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <form action="{{ route('notifications.markAllRead') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:underline">Mark all as read</button>
                                    </form>
                                @endif
                            </div>

                            <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                                @forelse(auth()->user()->notifications->take(5) as $notification)
                                    <a href="{{ $notification->data['link'] ?? '#' }}"
                                       class="block px-4 py-3 hover:bg-slate-50 transition {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50/40' }}">
                                        <p class="text-xs font-medium text-slate-800">{{ $notification->data['message'] ?? 'New notification received' }}</p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">{{ $notification->created_at->diffForHumans() }}</span>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-xs text-slate-400">No notifications yet</div>
                                @endforelse
                            </div>

                            <a href="{{ route('notifications.index') }}" class="block text-center text-xs text-blue-600 font-medium py-2 border-t border-slate-100 hover:bg-slate-50">
                                View All Notifications
                            </a>
                        </div>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 text-slate-700 hover:text-slate-900 font-medium focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold uppercase">
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </div>
                            <span class="hidden md:inline text-xs">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Profile Dropdown Menu -->
                        <div x-show="open" x-cloak x-transition
                             class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-slate-500 capitalize">{{ str_replace('_', ' ', auth()->user()->role ?? 'User') }}</p>
                            </div>

                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                👤 My Profile
                            </a>

                            @if(auth()->user()->isOwner())
                                <a href="{{ route('owner.verification.show') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                    🛡️ Verification Status
                                </a>
                            @endif

                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                📊 Dashboard
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs text-red-600 hover:bg-red-50">
                                    🚪 Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900 font-medium">Log in</a>
                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg px-3.5 py-1.5 transition">Sign up</a>
                @endauth
            </div>

        </div>
    </nav>

    @include('partials.desktop-back-button', ['fallbackUrl' => route('home')])

    <!-- Main Body Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 lg:py-10 flex-1 w-full">
        @yield('content')
    </main>

    <!-- Toast Notification for Flash Status -->
@if (session('status'))
    <div id="flash-status" data-message="{{ session('status') }}" class="hidden"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusElement = document.getElementById('flash-status');
            if (statusElement && statusElement.dataset.message) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: statusElement.dataset.message,
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                });
            }
        });
    </script>
@endif

</body>
</html>
