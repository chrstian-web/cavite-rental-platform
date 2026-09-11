@extends('layouts.app')

@section('title', 'Host Dashboard')

@section('content')
    @if (auth()->user()->isOwner() && ! auth()->user()->isOwnerVerified())
        <div class="mb-6 rounded-2xl border p-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between {{ auth()->user()->owner_verification_status === 'rejected' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }}">
            <div>
                <p class="text-sm font-bold {{ auth()->user()->owner_verification_status === 'rejected' ? 'text-red-800' : 'text-amber-800' }}">
                    @switch(auth()->user()->owner_verification_status)
                        @case('not_submitted') Complete owner verification to unlock property management. @break
                        @case('needs_review') Your verification documents are being reviewed. @break
                        @case('needs_additional_documents') We need a few more documents from you. @break
                        @case('rejected') Your owner verification was not approved. @break
                        @case('approved') Documents approved — email verification coming soon. @break
                        @default Owner verification required.
                    @endswitch
                </p>
                <p class="text-xs mt-1 {{ auth()->user()->owner_verification_status === 'rejected' ? 'text-red-600' : 'text-amber-600' }}">Property management features are locked until you're verified.</p>
            </div>
            <a href="{{ route('owner.verification.show') }}" class="text-sm font-bold bg-white border border-current rounded-xl px-4 py-2 shrink-0 {{ auth()->user()->owner_verification_status === 'rejected' ? 'text-red-700' : 'text-amber-700' }}">Continue Verification</a>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div><p class="eyebrow text-emerald-600 mb-2">Host portal</p><h1 class="text-3xl font-extrabold tracking-tight text-slate-950">Welcome, {{ auth()->user()->first_name }}</h1><p class="text-sm text-slate-500 mt-1">Keep your listings healthy and your tenants supported.</p></div>
        <a href="{{ route('owner.properties.create') }}" class="btn-primary btn-host">+ Add Property</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach ([
            'Properties' => $totalProperties, 'Total Units' => $totalUnits, 'Available Units' => $availableUnits, 'Occupied Units' => $occupiedUnits,
            'Pending Applications' => $pendingApplications, 'Active Tenants' => $activeTenants, 'Pending Maintenance' => $pendingMaintenance, 'Total Property Views' => $totalViews,
        ] as $label => $value)
            <div class="surface-card p-5"><p class="text-xs font-semibold text-slate-500">{{ $label }}</p><p class="text-2xl font-extrabold text-slate-950 mt-1">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="surface-card p-5"><p class="eyebrow">Revenue this month</p><p class="text-2xl font-extrabold text-emerald-600 mt-2">₱{{ number_format($monthlyRevenue, 2) }}</p><p class="text-xs text-slate-400 mt-1">Paid successfully</p></div>
        <div class="surface-card p-5"><p class="eyebrow">Outstanding payments</p><p class="text-2xl font-extrabold text-amber-600 mt-2">₱{{ number_format($outstandingPayments, 2) }}</p><p class="text-xs text-slate-400 mt-1">Pending + overdue</p></div>
    </div>

    <div class="surface-card p-6">
        <p class="eyebrow mb-4">Quick actions</p>
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('owner.properties.index') }}" class="btn-muted">Manage Properties</a>
            <a href="{{ route('owner.applications.index') }}" class="btn-muted">Review Applications</a>
            <a href="{{ route('owner.viewings.index') }}" class="btn-muted">Viewing Requests</a>
            <a href="{{ route('owner.contracts.index') }}" class="btn-muted">Manage Contracts</a>
            <a href="{{ route('owner.maintenance.index') }}" class="btn-muted">Maintenance Requests</a>
        </div>
    </div>
@endsection
