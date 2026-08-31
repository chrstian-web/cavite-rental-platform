@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if (auth()->user()->isOwner() && ! auth()->user()->isOwnerVerified())
        <div class="mb-6 rounded-xl border p-4 flex items-center justify-between
            {{ auth()->user()->owner_verification_status === 'rejected' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }}">
            <div>
                <p class="text-sm font-medium {{ auth()->user()->owner_verification_status === 'rejected' ? 'text-red-800' : 'text-amber-800' }}">
                    @switch(auth()->user()->owner_verification_status)
                        @case('not_submitted')
                            Complete owner verification to unlock property management.
                            @break
                        @case('needs_review')
                            Your verification documents are being reviewed.
                            @break
                        @case('needs_additional_documents')
                            We need a few more documents from you.
                            @break
                        @case('rejected')
                            Your owner verification was not approved.
                            @break
                        @case('approved')
                            Documents approved — email verification coming soon.
                            @break
                        @default
                            Owner verification required.
                    @endswitch
                </p>
                <p class="text-xs {{ auth()->user()->owner_verification_status === 'rejected' ? 'text-red-600' : 'text-amber-600' }}">
                    Property management features are locked until you're verified.
                </p>
            </div>
            <a href="{{ route('owner.verification.show') }}" class="text-sm font-medium bg-white border border-current rounded-lg px-4 py-2 shrink-0
                {{ auth()->user()->owner_verification_status === 'rejected' ? 'text-red-700' : 'text-amber-700' }}">
                Continue Verification
            </a>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Welcome, {{ auth()->user()->first_name }}</h1>
        <a href="{{ route('owner.properties.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
            + Add Property
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach ([
            'Properties' => $totalProperties,
            'Total Units' => $totalUnits,
            'Available Units' => $availableUnits,
            'Occupied Units' => $occupiedUnits,
            'Pending Applications' => $pendingApplications,
            'Active Tenants' => $activeTenants,
            'Pending Maintenance' => $pendingMaintenance,
            'Total Property Views' => $totalViews,
        ] as $label => $value)
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500">Revenue This Month (paid)</p>
            <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($monthlyRevenue, 2) }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500">Outstanding Payments (pending + overdue)</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">₱{{ number_format($outstandingPayments, 2) }}</p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-sm font-medium text-slate-700 mb-3">Quick Actions</p>
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('owner.properties.index') }}" class="text-blue-600 hover:underline">Manage Properties</a>
            <a href="{{ route('owner.applications.index') }}" class="text-blue-600 hover:underline">Review Applications</a>
            <a href="{{ route('owner.viewings.index') }}" class="text-blue-600 hover:underline">Viewing Requests</a>
            <a href="{{ route('owner.contracts.index') }}" class="text-blue-600 hover:underline">Manage Contracts</a>
            <a href="{{ route('owner.maintenance.index') }}" class="text-blue-600 hover:underline">Maintenance Requests</a>
        </div>
    </div>
@endsection
