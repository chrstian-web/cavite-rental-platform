@extends('layouts.app')

@section('title', 'Renter Dashboard')

@section('content')
    <div class="rt-hero">
        <p class="eyebrow rt-eyebrow">Renter dashboard</p>
        <h1>Welcome, {{ auth()->user()->first_name }}</h1>
        <p>Stay on top of your rental journey in Cavite.</p>
        @include('partials.landing-mascots')
    </div>

    @if ($availableProperties->isNotEmpty())
        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-extrabold text-slate-950">Homes available to rent</h2>
                <a href="{{ route('properties.index') }}" class="text-sm text-rose-600 hover:underline font-bold">View all →</a>
            </div>
            <div class="flex gap-4 overflow-x-auto pb-2 -mx-1 px-1 snap-x snap-mandatory">
                @foreach ($availableProperties as $property)
                    <div class="w-64 sm:w-72 shrink-0 snap-start">
                        @include('partials.property-card', ['property' => $property])
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="surface-card p-6"><p class="eyebrow mb-3">Current rental</p>
                @if ($activeContract)
    @if ($activeContract->property)
        <p class="text-lg text-slate-950 font-extrabold">{{ $activeContract->property->name }} <span class="text-slate-400 font-normal">— {{ $activeContract->rentalSpace->space_number }}</span></p>
        <p class="text-sm text-slate-500 mt-1">₱{{ number_format($activeContract->monthly_rent) }}/month · until {{ $activeContract->end_date->format('M j, Y') }}</p>
        <a href="{{ route('tenant.contracts.show', $activeContract) }}" class="text-sm text-rose-600 hover:underline font-bold mt-3 inline-block">View contract →</a>
    @else
        <p class="text-sm text-slate-400">Property details unavailable.</p>
    @endif
@else
    <p class="text-sm text-slate-400">No active rental yet.</p>
@endif
            </div>

            <div class="surface-card p-6"><div class="flex justify-between items-center mb-3"><p class="eyebrow">Recent applications</p><a href="{{ route('tenant.applications.index') }}" class="text-xs font-bold text-rose-600">View all</a></div>
                @forelse ($recentApplications as $application)
                    <div class="flex items-center justify-between text-sm py-3 border-b border-slate-100 last:border-0"><span class="text-slate-700 font-medium">{{ $application->property->name ?? 'Property unavailable' }}</span><span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ match($application->status) { 'approved' => 'bg-green-50 text-green-700', 'rejected', 'cancelled' => 'bg-red-50 text-red-700', default => 'bg-amber-50 text-amber-700' } }}">{{ str($application->status)->replace('_',' ')->title() }}</span></div>
                @empty <p class="text-sm text-slate-400">No applications yet.</p> @endforelse
            </div>

            <div class="surface-card p-6"><p class="eyebrow mb-3">Upcoming viewings</p>
                @forelse ($upcomingViewings as $viewing)
                    <div class="flex items-center justify-between text-sm py-3 border-b border-slate-100 last:border-0"><span class="text-slate-700 font-medium">{{ $viewing->property->name ?? 'Property unavailable' }}</span><span class="text-slate-400">{{ $viewing->preferred_date->format('M j') }} at {{ $viewing->preferred_time }}</span></div>
                @empty <p class="text-sm text-slate-400">No upcoming viewings.</p> @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-card p-6"><p class="eyebrow mb-2">Upcoming payment</p>@if ($upcomingPayment)<p class="text-2xl font-extrabold text-slate-950">₱{{ number_format($upcomingPayment->amount, 2) }}</p><p class="text-xs text-slate-400 mt-1">Due {{ $upcomingPayment->due_date->format('M j, Y') }}</p>@else<p class="text-sm text-slate-400">Nothing due right now.</p>@endif</div>
            <div class="rt-cta rounded-2xl p-6"><p class="rt-cta-title text-sm font-bold mb-1">Find a better fit</p><p class="rt-cta-text text-xs mb-4">Get properties scored against your exact preferences.</p><a href="{{ route('tenant.recommendations.create') }}" class="rt-cta-btn inline-block text-sm font-bold rounded-xl px-4 py-2.5 transition">Get recommendations</a></div>
            <div class="surface-card p-6"><p class="eyebrow mb-1">Favorites</p><p class="text-3xl font-extrabold text-slate-950">{{ $favoritesCount }}</p><a href="{{ route('tenant.favorites.index') }}" class="text-sm text-rose-600 hover:underline font-bold">View favorites →</a></div>
        </div>
    </div>
@endsection
