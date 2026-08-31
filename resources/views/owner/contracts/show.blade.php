@extends('layouts.app')

@section('title', 'Contract — '.$contract->property->name)

@section('content')
    <a href="{{ route('owner.contracts.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Contracts</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6">
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
                <dt class="text-slate-500">Tenant</dt><dd class="text-slate-900">{{ $contract->tenant->first_name }} {{ $contract->tenant->last_name }}</dd>
                <dt class="text-slate-500">Monthly rent</dt><dd class="text-slate-900">₱{{ number_format($contract->monthly_rent, 2) }}</dd>
                <dt class="text-slate-500">Security deposit</dt><dd class="text-slate-900">₱{{ number_format($contract->security_deposit, 2) }}</dd>
                <dt class="text-slate-500">Advance payment</dt><dd class="text-slate-900">₱{{ number_format($contract->advance_payment, 2) }}</dd>
                <dt class="text-slate-500">Lease term</dt><dd class="text-slate-900">{{ $contract->start_date->format('M j, Y') }} – {{ $contract->end_date->format('M j, Y') }}</dd>
            </dl>

            <div class="mt-4 pt-4 border-t border-slate-100 flex gap-3">
                <a href="{{ route('owner.contracts.pdf', $contract) }}" class="text-sm text-blue-600 hover:underline">Download PDF</a>
                @if ($contract->status === 'draft')
                    <form method="POST" action="{{ route('owner.contracts.activate', $contract) }}">
                        @csrf @method('PATCH')
                        <button class="text-sm text-green-600 hover:underline">Activate contract</button>
                    </form>
                @elseif ($contract->status === 'active')
                    <form method="POST" action="{{ route('owner.contracts.terminate', $contract) }}"
                          onsubmit="return confirm('Terminate this contract? This frees up the unit.')">
                        @csrf @method('PATCH')
                        <button class="text-sm text-red-600 hover:underline">Terminate contract</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <p class="text-sm font-medium text-slate-700 mb-3">Payments</p>

            @if ($contract->status === 'active')
                <form method="POST" action="{{ route('owner.contracts.payments.store', $contract) }}" class="space-y-2 mb-4 text-xs">
                    @csrf
                    <input type="number" step="0.01" name="amount" placeholder="Amount (₱)" required class="w-full rounded-lg border-slate-300">
                    <input type="date" name="due_date" required class="w-full rounded-lg border-slate-300">
                    <input type="date" name="payment_date" placeholder="Payment date (if paid)" class="w-full rounded-lg border-slate-300">
                    <select name="payment_method" class="w-full rounded-lg border-slate-300">
                        <option value="">Payment method</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="text" name="reference_number" placeholder="Reference # (optional)" class="w-full rounded-lg border-slate-300">
                    <select name="status" required class="w-full rounded-lg border-slate-300">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="failed">Failed</option>
                    </select>
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white rounded-lg py-1.5">Record payment</button>
                </form>
            @endif

            @if ($contract->payments->isEmpty())
                <p class="text-xs text-slate-400">No payments recorded yet.</p>
            @else
                <ul class="text-xs space-y-2">
                    @foreach ($contract->payments as $payment)
                        <li class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span>₱{{ number_format($payment->amount, 2) }} — due {{ $payment->due_date->format('M j') }}</span>
                            <span class="px-2 py-0.5 rounded-full
                                {{ match($payment->status) {
                                    'paid' => 'bg-green-50 text-green-700',
                                    'overdue', 'failed' => 'bg-red-50 text-red-700',
                                    default => 'bg-amber-50 text-amber-700',
                                } }}">
                                {{ str($payment->status)->title() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
