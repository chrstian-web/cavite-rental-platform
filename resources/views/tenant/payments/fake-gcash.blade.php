@extends('layouts.app')

@section('title', 'GCash payment')

@section('content')
    <div class="max-w-md mx-auto mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="bg-blue-700 px-6 py-6 text-center text-white">
            <p class="text-sm font-semibold tracking-wide">GCash · Send Money</p>
            <div class="mx-auto mt-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/90 text-xl font-semibold text-slate-600">
                {{ strtoupper(mb_substr($property?->contact_person ?: $property?->name ?: 'P', 0, 1)) }}
            </div>
            <p class="mt-3 text-lg font-semibold">{{ $recipientNumber }}</p>
            <p class="text-xs text-blue-100">{{ $property?->contact_person ?: $property?->name }}</p>
        </div>

        <form method="POST" action="{{ route('fake-checkout.pay', $checkout) }}" class="space-y-4 p-6">
            @csrf
            <input type="hidden" name="payment_method" value="gcash">

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Send to</p>
                <p class="mt-1 text-sm text-slate-900">{{ $recipientNumber }} — {{ $property?->name }}</p>
                <p class="text-xs text-slate-500">{{ $payment->typeLabel() }}. Amount due: <strong>₱{{ number_format((float) $payment->amount, 2) }}</strong></p>
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-slate-700 mb-1">Amount you are sending (₱)</label>
                <input id="amount" type="number" name="amount" step="0.01" min="0.01" required inputmode="decimal"
                       value="{{ old('amount') }}" placeholder="0.00"
                       class="w-full rounded-lg border-slate-300 text-lg">
                @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Pay</button>
            <a href="{{ route('fake-checkout.show', $checkout) }}" class="block text-center text-sm text-slate-500 hover:underline">Back</a>
        </form>
    </div>
@endsection
