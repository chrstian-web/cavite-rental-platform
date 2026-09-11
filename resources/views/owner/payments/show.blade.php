@extends('layouts.app')

@section('title', 'Payment Review')

@section('content')
    <a href="{{ route('owner.payments.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>Payments</span></a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $payment->tenant->first_name }} {{ $payment->tenant->last_name }}</h1>
                    @include('partials.payment-status-badge', ['payment' => $payment])
                </div>

                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Property</dt><dd class="text-slate-900">{{ $payment->contract->property->name }}</dd>
                    <dt class="text-slate-500">Amount</dt><dd class="text-slate-900 font-medium">₱{{ number_format($payment->amount, 2) }}</dd>
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
                    @if ($payment->reviewer)
                        <dt class="text-slate-500">Last reviewed by</dt><dd class="text-slate-900">{{ $payment->reviewer->first_name }} {{ $payment->reviewer->last_name }}</dd>
                    @endif
                </dl>

                @if ($payment->proof_path)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('payments.proof.download', $payment) }}" class="text-sm text-blue-600 hover:underline">
                            Download submitted proof of payment
                        </a>
                    </div>
                @else
                    <p class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400">No proof file was attached to this submission.</p>
                @endif

                @if ($payment->status === 'paid')
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('owner.payments.receipt', $payment) }}" class="text-sm text-blue-600 hover:underline">Download receipt (PDF)</a>
                    </div>
                @endif
            </div>

            @if ($payment->canBeReviewed())
                <div class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Review this submission</h2>

                    @include('partials.validation-errors')

                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('owner.payments.review', $payment) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="min-h-[44px] px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl">
                                Approve
                            </button>
                        </form>

                        <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                                class="min-h-[44px] px-5 py-2.5 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium rounded-xl">
                            Reject
                        </button>

                        <button type="button" onclick="document.getElementById('correction-form').classList.toggle('hidden')"
                                class="min-h-[44px] px-5 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-sm font-medium rounded-xl">
                            Request correction
                        </button>
                    </div>

                    <form id="reject-form" method="POST" action="{{ route('owner.payments.review', $payment) }}" class="hidden mt-4 space-y-2">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="reject">
                        <label class="block text-xs font-medium text-slate-500">Reason for rejection</label>
                        <textarea name="reason" required maxlength="1000" rows="3" class="w-full rounded-lg border-slate-300 text-sm"
                                  placeholder="e.g. reference number doesn't match our records"></textarea>
                        <button type="submit" class="text-sm px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">Confirm rejection</button>
                    </form>

                    <form id="correction-form" method="POST" action="{{ route('owner.payments.review', $payment) }}" class="hidden mt-4 space-y-2">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="request_correction">
                        <label class="block text-xs font-medium text-slate-500">What needs to be corrected?</label>
                        <textarea name="reason" required maxlength="1000" rows="3" class="w-full rounded-lg border-slate-300 text-sm"
                                  placeholder="e.g. please re-upload a clearer photo of the receipt"></textarea>
                        <button type="submit" class="text-sm px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">Send back for correction</button>
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
