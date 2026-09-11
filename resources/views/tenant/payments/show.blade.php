@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
    <a href="{{ route('tenant.payments.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>My Payments</span></a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $payment->contract->property->name }}</h1>
                    @include('partials.payment-status-badge', ['payment' => $payment])
                </div>

                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Amount due</dt><dd class="text-slate-900 font-medium">₱{{ number_format($payment->amount, 2) }}</dd>
                    <dt class="text-slate-500">Due date</dt><dd class="text-slate-900">{{ $payment->due_date->format('M j, Y') }}</dd>
                    @if ($payment->payment_date)
                        <dt class="text-slate-500">Payment date</dt><dd class="text-slate-900">{{ $payment->payment_date->format('M j, Y') }}</dd>
                    @endif
                    @if ($payment->payment_method)
                        <dt class="text-slate-500">Method</dt><dd class="text-slate-900">{{ str($payment->payment_method)->title() }}</dd>
                    @endif
                    @if ($payment->reference_number)
                        <dt class="text-slate-500">Reference #</dt><dd class="text-slate-900">{{ $payment->reference_number }}</dd>
                    @endif
                </dl>

                @if ($payment->proof_path)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('payments.proof.download', $payment) }}" class="text-sm text-blue-600 hover:underline">
                            View submitted proof of payment
                        </a>
                    </div>
                @endif

                @if ($payment->status === 'paid')
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('tenant.payments.receipt', $payment) }}" class="text-sm text-blue-600 hover:underline">Download receipt (PDF)</a>
                    </div>
                @endif
            </div>

            @if (in_array($payment->status, ['failed', 'pending']) && $payment->review_reason)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    <p class="font-medium mb-1">{{ $payment->status === 'failed' ? 'Payment rejected' : 'Correction needed' }}</p>
                    <p>{{ $payment->review_reason }}</p>
                </div>
            @endif

            @if ($payment->canBeSubmittedByTenant())
                <div class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Submit this payment</h2>

                    @include('partials.validation-errors')

                    <form method="POST" action="{{ route('tenant.payments.submit', $payment) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Amount</label>
                            <input type="text" value="₱{{ number_format($payment->amount, 2) }}" disabled
                                   class="w-full rounded-lg border-slate-300 bg-slate-50 text-slate-500">
                            <input type="hidden" name="amount" value="{{ $payment->amount }}">
                            <p class="text-xs text-slate-400 mt-1">This is the exact amount due — partial payments aren't supported yet.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Payment method</label>
                            <select name="payment_method" required class="w-full rounded-lg border-slate-300">
                                <option value="">Select method</option>
                                <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                                <option value="gcash" @selected(old('payment_method') === 'gcash')>GCash</option>
                                <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option>
                                <option value="other" @selected(old('payment_method') === 'other')>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Payment date</label>
                            <input type="date" name="payment_date" required max="{{ now()->toDateString() }}"
                                   value="{{ old('payment_date', now()->toDateString()) }}" class="w-full rounded-lg border-slate-300">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Reference number</label>
                            <input type="text" name="reference_number" required maxlength="100" value="{{ old('reference_number') }}"
                                   placeholder="e.g. GCash transaction ID" class="w-full rounded-lg border-slate-300">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Proof of payment (optional)</label>
                            <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm">
                            <p class="text-xs text-slate-400 mt-1">JPG, PNG, or PDF — up to 5MB.</p>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 text-sm font-medium min-h-[44px]">
                            Submit payment for review
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <p class="text-sm font-medium text-slate-700 mb-3">Activity</p>
            @if ($payment->statusHistories->isEmpty())
                <p class="text-xs text-slate-400">No activity yet.</p>
            @else
                <ul class="text-xs space-y-3">
                    @foreach ($payment->statusHistories as $history)
                        <li class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <p class="text-slate-900 font-medium">{{ str($history->to_status)->title() }}</p>
                            <p class="text-slate-500">{{ $history->created_at->format('M j, Y g:i A') }}
                                @if ($history->actor)
                                    &middot; {{ $history->actor->first_name }} {{ $history->actor->last_name }}
                                @endif
                            </p>
                            @if ($history->reason)
                                <p class="text-slate-500 mt-1">{{ $history->reason }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
