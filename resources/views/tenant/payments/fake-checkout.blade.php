@extends('layouts.app')

@section('title', 'Test checkout')

@section('content')
    <div class="max-w-md mx-auto mt-8 bg-white border border-slate-200 rounded-xl p-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">
            Test checkout — no real money is charged
        </p>

        <h1 class="text-lg font-semibold text-slate-900">{{ $payment->typeLabel() }}</h1>
        <p class="text-sm text-slate-500">{{ $payment->contract->property->name }}</p>

        <p class="text-3xl font-semibold text-slate-900 my-6">₱{{ number_format($payment->amount, 2) }}</p>

        <form method="POST" action="{{ route('fake-checkout.pay', $checkout) }}" class="space-y-2">
            @csrf
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-medium">
                Pay ₱{{ number_format($payment->amount, 2) }} (simulate GCash)
            </button>
        </form>

        <form method="POST" action="{{ route('fake-checkout.cancel', $checkout) }}" class="mt-2">
            @csrf
            <button type="submit" class="w-full border border-slate-300 text-slate-700 rounded-lg py-2 text-sm">Cancel</button>
        </form>
    </div>
@endsection
