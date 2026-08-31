@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Admin Dashboard</h1>

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
