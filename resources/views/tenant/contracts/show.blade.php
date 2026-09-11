@extends('layouts.app')

@section('title', 'My Contract')

@section('content')
    <a href="{{ route('tenant.contracts.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>My Contracts</span></a>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mt-4 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold text-slate-900">{{ $contract->property->name }} — {{ $contract->rentalSpace->space_number }}</h1>
            <span class="text-xs px-2 py-1 rounded-full
                {{ match($contract->status) {
                    'active' => 'bg-green-50 text-green-700',
                    'terminated' => 'bg-red-50 text-red-700',
                    'expired' => 'bg-slate-100 text-slate-600',
                    default => 'bg-amber-50 text-amber-700',
                } }}">
                {{ str($contract->status)->title() }}
            </span>
        </div>

        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-slate-500">Monthly rent</dt><dd class="text-slate-900">₱{{ number_format($contract->monthly_rent, 2) }}</dd>
            <dt class="text-slate-500">Security deposit</dt><dd class="text-slate-900">₱{{ number_format($contract->security_deposit, 2) }}</dd>
            <dt class="text-slate-500">Lease term</dt><dd class="text-slate-900">{{ $contract->start_date->format('M j, Y') }} – {{ $contract->end_date->format('M j, Y') }}</dd>
        </dl>

        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-3">
            <a href="{{ route('tenant.contracts.pdf', $contract) }}" class="text-sm text-blue-600 hover:underline">Download PDF</a>
            @if ($contract->status === 'active')
                <a href="{{ route('tenant.maintenance.create', $contract) }}" class="text-sm text-blue-600 hover:underline">Submit maintenance request</a>
            @endif
            @if (in_array($contract->status, ['expired', 'terminated']) && ! $contract->review)
                <a href="{{ route('tenant.contracts.review.create', $contract) }}" class="text-sm text-blue-600 hover:underline">Leave a review</a>
            @endif
        </div>

        @if ($contract->payments->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm font-medium text-slate-700 mb-2">Payment history</p>
                <ul class="text-sm space-y-1">
                    @foreach ($contract->payments as $payment)
                        <li class="flex items-center justify-between">
                            <a href="{{ route('tenant.payments.show', $payment) }}" class="hover:underline">
                                ₱{{ number_format($payment->amount, 2) }} — due {{ $payment->due_date->format('M j, Y') }}
                            </a>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ match($payment->status) {
                                    'paid' => 'bg-green-50 text-green-700',
                                    'overdue', 'failed' => 'bg-red-50 text-red-700',
                                    'submitted' => 'bg-blue-50 text-blue-700',
                                    default => 'bg-amber-50 text-amber-700',
                                } }}">
                                {{ str($payment->status)->title() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
