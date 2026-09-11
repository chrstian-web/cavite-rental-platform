<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · Cavite Rental Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200" x-data="{ mobileOpen: false }">
        <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="font-bold text-slate-900 shrink-0">Cavite<span class="text-blue-600">Rentals</span></a>

                {{-- Desktop nav links, grouped into dropdowns per role --}}
                <div class="hidden md:flex items-center gap-6">
                    @if (in_array(auth()->user()->role->slug, ['owner', 'manager', 'super_admin']))
                        @include('partials.nav-link', ['route' => 'owner.properties.index', 'label' => 'My Properties', 'notifyTypes' => 'property_verification'])
                        @include('partials.nav-dropdown', ['label' => 'Rentals', 'links' => [
                            ['route' => 'owner.applications.index', 'label' => 'Applications', 'notifyTypes' => 'new_rental_application'],
                            ['route' => 'owner.viewings.index', 'label' => 'Viewing Requests', 'notifyTypes' => 'new_viewing_request'],
                            ['route' => 'owner.contracts.index', 'label' => 'Contracts'],
                            ['route' => 'owner.maintenance.index', 'label' => 'Maintenance', 'notifyTypes' => 'new_maintenance_request'],
                        ]])
                    @endif
                    @if (auth()->user()->isTenant())
                        <a href="{{ route('tenant.recommendations.create') }}" class="text-sm text-slate-600 hover:text-slate-900">Recommended for You</a>
                        <a href="{{ route('tenant.favorites.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Favorites</a>
                        @include('partials.nav-dropdown', ['label' => 'My Rentals', 'links' => [
                            ['route' => 'tenant.applications.index', 'label' => 'My Applications', 'notifyTypes' => 'rental_application_status'],
                            ['route' => 'tenant.viewings.index', 'label' => 'My Viewings', 'notifyTypes' => 'viewing_request_status'],
                            ['route' => 'tenant.contracts.index', 'label' => 'My Contracts', 'notifyTypes' => 'contract_activated'],
                            ['route' => 'tenant.payments.index', 'label' => 'My Payments'],
                            ['route' => 'tenant.maintenance.index', 'label' => 'Maintenance', 'notifyTypes' => 'maintenance_request_status'],
                        ]])
                    @endif
                    @if (auth()->user()->isSuperAdmin())
                        @include('partials.nav-dropdown', ['label' => 'Manage', 'links' => [
                            ['route' => 'admin.properties.index', 'label' => 'Verify Properties', 'notifyTypes' => 'new_property_submitted'],
                            ['route' => 'admin.owner-verifications.index', 'label' => 'Owner Verifications', 'notifyTypes' => 'new_owner_verification_submitted'],
                            ['route' => 'admin.dss.index', 'label' => 'DSS Configuration'],
                            ['route' => 'admin.reports.index', 'label' => 'Reports'],
                            ['route' => 'admin.users.index', 'label' => 'Manage Users'],
                        ]])
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-4">
                @include('partials.nav-search')
                @include('partials.notification-bell')
                @include('partials.profile-dropdown')

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu: flat list of every link, since nested dropdowns are awkward on small screens --}}
        <div x-show="mobileOpen" x-cloak x-transition class="md:hidden border-t border-slate-100 px-4 py-3 space-y-2">
            @if (in_array(auth()->user()->role->slug, ['owner', 'manager', 'super_admin']))
                <a href="{{ route('owner.properties.index') }}" class="block text-sm text-slate-600 py-1">My Properties</a>
                <a href="{{ route('owner.applications.index') }}" class="block text-sm text-slate-600 py-1">Applications</a>
                <a href="{{ route('owner.viewings.index') }}" class="block text-sm text-slate-600 py-1">Viewing Requests</a>
                <a href="{{ route('owner.contracts.index') }}" class="block text-sm text-slate-600 py-1">Contracts</a>
                <a href="{{ route('owner.maintenance.index') }}" class="block text-sm text-slate-600 py-1">Maintenance</a>
            @endif
            @if (auth()->user()->isTenant())
                <a href="{{ route('tenant.recommendations.create') }}" class="block text-sm text-slate-600 py-1">Recommended for You</a>
                <a href="{{ route('tenant.favorites.index') }}" class="block text-sm text-slate-600 py-1">Favorites</a>
                <a href="{{ route('tenant.applications.index') }}" class="block text-sm text-slate-600 py-1">My Applications</a>
                <a href="{{ route('tenant.viewings.index') }}" class="block text-sm text-slate-600 py-1">My Viewings</a>
                <a href="{{ route('tenant.contracts.index') }}" class="block text-sm text-slate-600 py-1">My Contracts</a>
                <a href="{{ route('tenant.payments.index') }}" class="block text-sm text-slate-600 py-1">My Payments</a>
                <a href="{{ route('tenant.maintenance.index') }}" class="block text-sm text-slate-600 py-1">Maintenance</a>
            @endif
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.properties.index') }}" class="block text-sm text-slate-600 py-1">Verify Properties</a>
                <a href="{{ route('admin.owner-verifications.index') }}" class="block text-sm text-slate-600 py-1">Owner Verifications</a>
                <a href="{{ route('admin.dss.index') }}" class="block text-sm text-slate-600 py-1">DSS Configuration</a>
                <a href="{{ route('admin.reports.index') }}" class="block text-sm text-slate-600 py-1">Reports</a>
                <a href="{{ route('admin.users.index') }}" class="block text-sm text-slate-600 py-1">Manage Users</a>
            @endif
            <a href="{{ route('properties.index') }}" class="block text-sm text-blue-600 py-1">Browse All Properties</a>
        </div>
    </nav>

    @include('partials.desktop-back-button', ['fallbackUrl' => route('dashboard')])

    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    @if (session('status'))
        <script>
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: @json(session('status')), showConfirmButton: false, timer: 3500, timerProgressBar: true,
            });
        </script>
    @endif

    @if ($errors->any())
        @php
            $extraCount = $errors->count() - 1;
            $errorToastText = $extraCount > 0 ? "+ {$extraCount} more issue(s) below" : '';
        @endphp
        <script>
            Swal.fire({
                toast: true, position: 'top-end', icon: 'error',
                title: @json($errors->first()), text: @json($errorToastText),
                showConfirmButton: false, timer: 5000, timerProgressBar: true,
            });
        </script>
        <div class="max-w-6xl mx-auto px-4">
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</body>
</html>
