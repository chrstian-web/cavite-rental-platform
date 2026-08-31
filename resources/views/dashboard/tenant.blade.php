@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Welcome, {{ auth()->user()->first_name }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm font-medium text-slate-700 mb-3">Current Rental</p>
                @if ($activeContract)
                    <p class="text-slate-900 font-medium">{{ $activeContract->property->name }} — {{ $activeContract->rentalSpace->space_number }}</p>
                    <p class="text-sm text-slate-500">₱{{ number_format($activeContract->monthly_rent) }}/month &middot; until {{ $activeContract->end_date->format('M j, Y') }}</p>
                    <a href="{{ route('tenant.contracts.show', $activeContract) }}" class="text-sm text-blue-600 hover:underline mt-2 inline-block">View contract</a>
                @else
                    <p class="text-sm text-slate-400">No active rental yet.</p>
                @endif
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm font-medium text-slate-700 mb-3">Recent Applications</p>
                @forelse ($recentApplications as $application)
                    <div class="flex items-center justify-between text-sm py-1.5 border-b border-slate-50 last:border-0">
                        <span class="text-slate-600">{{ $application->property->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ match($application->status) {
                                'approved' => 'bg-green-50 text-green-700',
                                'rejected', 'cancelled' => 'bg-red-50 text-red-700',
                                default => 'bg-amber-50 text-amber-700',
                            } }}">
                            {{ str($application->status)->replace('_',' ')->title() }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No applications yet.</p>
                @endforelse
                <a href="{{ route('tenant.applications.index') }}" class="text-sm text-blue-600 hover:underline mt-3 inline-block">View all</a>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm font-medium text-slate-700 mb-3">Upcoming Viewings</p>
                @forelse ($upcomingViewings as $viewing)
                    <div class="flex items-center justify-between text-sm py-1.5 border-b border-slate-50 last:border-0">
                        <span class="text-slate-600">{{ $viewing->property->name }}</span>
                        <span class="text-slate-400">{{ $viewing->preferred_date->format('M j') }} at {{ $viewing->preferred_time }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No upcoming viewings.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm font-medium text-slate-700 mb-2">Upcoming Payment</p>
                @if ($upcomingPayment)
                    <p class="text-lg font-bold text-slate-900">₱{{ number_format($upcomingPayment->amount, 2) }}</p>
                    <p class="text-xs text-slate-400">Due {{ $upcomingPayment->due_date->format('M j, Y') }}</p>
                @else
                    <p class="text-sm text-slate-400">Nothing due right now.</p>
                @endif
            </div>

            <div class="bg-blue-600 rounded-xl p-5 text-white">
                <p class="text-sm font-medium mb-1">Personalized Recommendations</p>
                <p class="text-xs text-blue-100 mb-3">Get properties scored against your exact preferences.</p>
                <a href="{{ route('tenant.recommendations.create') }}" class="inline-block bg-white text-blue-700 text-sm font-medium rounded-lg px-4 py-2">
                    Get Recommendations
                </a>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm font-medium text-slate-700 mb-1">Favorites</p>
                <p class="text-2xl font-bold text-slate-900">{{ $favoritesCount }}</p>
                <a href="{{ route('tenant.favorites.index') }}" class="text-sm text-blue-600 hover:underline">View favorites</a>
            </div>
        </div>
    </div>
@endsection
