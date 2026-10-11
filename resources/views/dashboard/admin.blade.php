@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Admin Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">What needs your decision, and how the platform is doing.</p>
    </div>

    {{-- Needs your attention: the admin's work queue --}}
    @php $queueTotal = $pendingOwnerVerifications + $pendingMoreDocuments + $pendingPropertyVerifications; @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach ([
            ['Owner verifications to review', $pendingOwnerVerifications, route('admin.owner-verifications.index', ['status' => 'needs_review']), 'Review'],
            ['Waiting on owner documents', $pendingMoreDocuments, route('admin.owner-verifications.index', ['status' => 'needs_additional_documents']), 'View'],
            ['Properties awaiting verification', $pendingPropertyVerifications, route('admin.properties.index', ['status' => 'pending']), 'Verify'],
        ] as [$label, $count, $url, $action])
            <a href="{{ $url }}" class="block bg-white border {{ $count > 0 ? 'border-rose-300' : 'border-slate-200' }} rounded-xl p-5 hover:border-rose-400 transition">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <div class="flex items-end justify-between mt-2">
                    <p class="text-3xl font-bold {{ $count > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $count }}</p>
                    <span class="text-xs font-bold {{ $count > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $count > 0 ? $action : 'Nothing pending' }}</span>
                </div>
            </a>
        @endforeach
    </div>
    @if ($queueTotal === 0)
        <p class="text-sm text-slate-500 -mt-2 mb-6">You're all caught up. Nothing is waiting for review.</p>
    @endif

    {{-- Platform totals --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('admin.properties.index') }}" class="block bg-white border border-slate-200 rounded-xl p-4 hover:border-slate-400 transition">
            <p class="text-xs text-slate-500">Properties</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalProperties }}</p>
        </a>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500">Units occupied</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $occupiedUnits }} <span class="text-sm font-medium text-slate-400">of {{ $totalUnits }}</span></p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="block bg-white border border-slate-200 rounded-xl p-4 hover:border-slate-400 transition">
            <p class="text-xs text-slate-500">Users</p>
            <p class="text-sm text-slate-700 mt-2">
                <span class="font-bold text-slate-900">{{ $totalOwners }}</span> owners ·
                <span class="font-bold text-slate-900">{{ $totalManagers }}</span> managers ·
                <span class="font-bold text-slate-900">{{ $totalTenants }}</span> tenants
            </p>
            @if ($deletedUsers > 0)
                <p class="text-xs text-slate-400 mt-1">{{ $deletedUsers }} deleted</p>
            @endif
        </a>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500">Revenue this month (paid)</p>
            <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($monthlyRevenue, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Property owners --}}
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
                <div>
                    <p class="text-sm font-medium text-slate-700">Property Owners</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $totalOwners }} registered{{ $totalOwners > $owners->count() ? ', showing top '.$owners->count().' by properties' : '' }}</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-rose-600 hover:underline">All users</a>
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
                                <th class="px-5 py-2.5 font-medium text-right">Tenants</th>
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
                                    $latest = $owner->latest_verification;
                                @endphp
                                <tr>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.users.index', ['search' => $owner->email]) }}" class="font-medium text-slate-900 hover:underline">{{ $owner->first_name }} {{ $owner->last_name }}</a>
                                        <p class="text-xs text-slate-400">{{ $owner->email }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($latest)
                                            <a href="{{ route('admin.owner-verifications.show', $latest) }}" class="text-xs px-2 py-1 rounded-full hover:underline {{ $vsClass }}">{{ str($vs)->replace('_', ' ')->title() }}</a>
                                        @else
                                            <span class="text-xs px-2 py-1 rounded-full {{ $vsClass }}">{{ str($vs)->replace('_', ' ')->title() }}</span>
                                        @endif
                                    </td>
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

        {{-- Recent activity --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-sm font-medium text-slate-700 mb-3">Recent Activity</p>
            <ul class="space-y-3">
                @forelse ($recentActivity as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="block text-sm text-slate-700 hover:underline">{{ $item['text'] }}</a>
                        <p class="text-xs text-slate-400">{{ $item['at']->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No activity yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-400 transition">Reports</a>
        <a href="{{ route('admin.dss.index') }}" class="text-sm font-medium px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-400 transition">DSS Configuration</a>
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-400 transition">Manage Users</a>
        <a href="{{ route('admin.users.deleted') }}" class="text-sm font-medium px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-400 transition">Deleted Users</a>
    </div>
@endsection
