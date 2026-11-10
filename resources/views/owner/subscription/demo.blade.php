@extends('layouts.app')

@section('title', 'Demo checkout')

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-amber-300 bg-white p-6">
        <p class="text-xs font-semibold text-amber-700">DEMO CHECKOUT: no real payment is taken. Local development only.</p>
        <h1 class="mt-2 text-xl font-semibold text-slate-900">{{ $plan['name'] }} plan</h1>
        <p class="mt-1 text-2xl font-extrabold text-slate-900">₱{{ number_format($subscription->amount) }} <span class="text-sm font-normal text-slate-500">for {{ config('subscriptions.period_days') }} days</span></p>
        <form method="POST" action="{{ route('owner.subscription.demo.confirm', $subscription) }}" class="mt-5">
            @csrf
            <button type="submit" class="w-full rounded-full bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Pay ₱{{ number_format($subscription->amount) }} (demo)</button>
        </form>
        <a href="{{ route('owner.subscription.index', ['checkout' => 'cancelled']) }}" class="mt-3 block text-center text-sm text-slate-500 underline">Cancel</a>
    </div>
@endsection
