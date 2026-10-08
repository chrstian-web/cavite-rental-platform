@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="rt-hero">
        <p class="eyebrow rt-eyebrow">Admin console</p>
        <h1>Admin Dashboard</h1>
        <p>Platform health, verifications and activity at a glance.</p>
        @include('partials.landing-mascots')
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach ([
            'Properties' => $totalProperties,
            'Total Units' => $totalUnits,
            'Available Units' => $availableUnits,
            'Occupied Units' => $occupiedUnits,
            'Tenants' => $totalTenants,
            'Pending Applications' => $pendingApplications,
            'Overdue Payments' => $overduePayments,
            'Pending Maintenance' => $pendingMaintenance,
        ] as $label => $value)
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $value }}</p>
            </div>
        @endforeach
        <div class="bg-white border border-slate-200 rounded-xl p-4 col-span-2">
            <p class="text-xs text-slate-500">Revenue This Month (paid)</p>
            <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($monthlyRevenue, 2) }}</p>
        </div>
    </div>

    {{-- Property owners --}}
    <div class="mb-8 bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
            <div>
                <p class="text-sm font-medium text-slate-700">Property Owners</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $totalOwners }} registered{{ $totalOwners > $owners->count() ? ' · showing top '.$owners->count().' by properties' : '' }}</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-bold">
                <a href="{{ route('admin.owner-verifications.index') }}" class="text-rose-600 hover:underline">Verifications</a>
                <a href="{{ route('admin.users.index') }}" class="text-rose-600 hover:underline">All users →</a>
            </div>
        </div>

        @if ($owners->isEmpty())
            <p class="px-5 py-8 text-sm text-slate-400 text-center">No property owners yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Owner</th>
                            <th class="px-3 py-2.5 font-medium">Verification</th>
                            <th class="px-3 py-2.5 font-medium text-right">Properties</th>
                            <th class="px-3 py-2.5 font-medium text-right">Units</th>
                            <th class="px-3 py-2.5 font-medium text-right">Occupied</th>
                            <th class="px-5 py-2.5 font-medium text-right">Active tenants</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($owners as $owner)
                            @php
                                $vs = $owner->owner_verification_status ?? 'not_submitted';
                                $vsClass = match ($vs) {
                                    'verified', 'approved' => 'bg-green-50 text-green-700',
                                    'rejected' => 'bg-red-50 text-red-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.users.index', ['search' => $owner->email]) }}" class="font-medium text-slate-900 hover:underline">{{ $owner->first_name }} {{ $owner->last_name }}</a>
                                    <p class="text-xs text-slate-400">{{ $owner->email }}</p>
                                </td>
                                <td class="px-3 py-3"><span class="text-xs px-2 py-1 rounded-full {{ $vsClass }}">{{ str($vs)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-3 py-3 text-right text-slate-700">{{ $owner->properties_count }}</td>
                                <td class="px-3 py-3 text-right text-slate-700">{{ $owner->units_count }}</td>
                                <td class="px-3 py-3 text-right text-slate-700">{{ $owner->occupied_count }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $owner->active_tenants_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-sm font-medium text-slate-700 mb-3">Property Type Distribution</p>
            @php $totalTyped = $typeDistribution->sum('total') ?: 1; @endphp
            <div class="space-y-2">
                @foreach ($typeDistribution as $row)
                    <div>
                        <div class="flex justify-between text-xs text-slate-500 mb-1">
                            <span>{{ str($row->property_type)->replace('_',' ')->title() }}</span>
                            <span>{{ $row->total }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" style="width: {{ round(($row->total / $totalTyped) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-sm font-medium text-slate-700 mb-3">Most Viewed Properties</p>
            <ul class="text-sm space-y-2">
                @forelse ($mostViewed as $p)
                    <li class="flex justify-between text-slate-600"><span>{{ $p->name }}</span><span class="text-slate-400">{{ $p->views_count }} views</span></li>
                @empty
                    <li class="text-slate-400">No data yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-sm font-medium text-slate-700 mb-3">Popular Locations</p>
            <ul class="text-sm space-y-2">
                @forelse ($popularLocations as $row)
                    <li class="flex justify-between text-slate-600">
                        <span>{{ $row->location->city_municipality ?? '—' }}</span>
                        <span class="text-slate-400">{{ $row->total }} listing(s)</span>
                    </li>
                @empty
                    <li class="text-slate-400">No data yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-sm font-medium text-slate-700 mb-3">Most Favorited Properties</p>
        <ul class="text-sm space-y-2">
            @forelse ($mostFavorited as $p)
                <li class="flex justify-between text-slate-600"><span>{{ $p->name }}</span><span class="text-slate-400">{{ $p->favorites_count }} favorite(s)</span></li>
            @empty
                <li class="text-slate-400">No data yet.</li>
            @endforelse
        </ul>
    </div>
@endsection
