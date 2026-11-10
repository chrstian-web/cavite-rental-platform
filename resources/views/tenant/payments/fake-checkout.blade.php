@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <div class="max-w-md mx-auto mt-8 bg-white border border-slate-200 rounded-xl p-6">
        <h1 class="text-lg font-semibold text-slate-900">{{ $payment->typeLabel() }}</h1>
        <p class="text-sm text-slate-500">{{ $payment->application?->property?->name ?? $payment->contract?->property?->name }}</p>

        <p class="text-3xl font-semibold text-slate-900 my-6">₱{{ number_format($payment->amount, 2) }}</p>

        <form method="POST" action="{{ route('fake-checkout.continue', $checkout) }}" class="space-y-3">
            @csrf
            <fieldset>
                <legend class="mb-2 text-sm font-medium text-slate-700">Choose a payment method</legend>
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input type="radio" name="payment_method" value="gcash" required @checked(old('payment_method', 'gcash') === 'gcash')>
                    <span>GCash</span>
                </label>
                <label class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input type="radio" name="payment_method" value="bank_transfer" required @checked(old('payment_method') === 'bank_transfer')>
                    <span>Bank transfer</span>
                </label>
                @error('payment_method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </fieldset>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-medium">
                Complete payment
            </button>
        </form>

        <form method="POST" action="{{ route('fake-checkout.cancel', $checkout) }}" class="mt-2">
            @csrf
            <button type="submit" class="w-full border border-slate-300 text-slate-700 rounded-lg py-2 text-sm">Cancel</button>
        </form>
    </div>
@endsection
