@extends('layouts.app')

@section('title', 'Subscription')

@section('content')
    @php
        $subs = app(\App\Services\SubscriptionService::class);
        $user = auth()->user();
        $count = $subs->propertyCount($user);
        $chosen = $subs->hasChosen($user);
        $hidden = $user->properties()->where('subscription_hidden', true)->count();
    @endphp

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Subscription plans</h1>
        <p class="mt-1 text-sm text-slate-600">
            @if ($chosen)
                You're on <span class="font-semibold">{{ $subs->plan($currentKey)['name'] }}</span>@if ($active && $active->ends_at), active until {{ $active->ends_at->format('M j, Y') }}@endif.
            @else
                You haven't picked a plan yet. All your properties stay visible until you do.
            @endif
        </p>
    </div>

    @if (request('checkout') === 'success' && ! $chosen)
        <div class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">We're confirming your payment. Your plan starts as soon as the payment goes through. Refresh this page in a moment.</div>
    @elseif (request('checkout') === 'cancelled')
        <div class="mb-4 rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-700">Checkout cancelled. You were not charged.</div>
    @endif

    @if ($hidden > 0)
        <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $hidden }} of your {{ $count }} {{ \Illuminate\Support\Str::plural('property', $count) }} {{ $hidden === 1 ? 'is' : 'are' }} hidden from tenants. Upgrade to show {{ $hidden === 1 ? 'it' : 'them' }} again.
        </div>
    @endif

    <div x-data="{ confirmFree: false, confirmedFree: false }">
        <div x-show="! confirmFree">
            @include('partials.subscription-plans', [
                'plans' => $subs->plans(), 'currentKey' => $currentKey,
                'recommendedKey' => $count > 0 ? $subs->recommend($count) : null,
                'propertyCount' => $count, 'chosen' => $chosen,
            ])
        </div>

        <div x-show="confirmFree" x-cloak class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <h2 class="text-lg font-bold text-slate-900">Switch to the Free plan?</h2>
            <p class="mt-2 text-sm text-slate-600">Only 2 of your {{ $count }} properties will stay visible to tenants. The other {{ max(0, $count - 2) }} will be hidden.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <form method="POST" action="{{ route('owner.subscription.store') }}">
                    @csrf
                    <input type="hidden" name="plan" value="free">
                    <button type="submit" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">Continue with Free</button>
                </form>
                <button type="button" class="rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" x-on:click="confirmFree = false">Back to plans</button>
            </div>
        </div>
    </div>
@endsection
