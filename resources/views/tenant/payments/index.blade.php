@extends('layouts.app')

@section('title', 'My Payments')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">My Payments</h1>

    @if ($payments->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">No payments recorded yet.</div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Property</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Due date</th>
                        <th class="px-4 py-3">Paid on</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->contract->property->name }}</td>
                            <td class="px-4 py-3 text-slate-600">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->due_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->payment_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ match($payment->status) {
                                        'paid' => 'bg-green-50 text-green-700',
                                        'overdue', 'failed' => 'bg-red-50 text-red-700',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ str($payment->status)->title() }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $payments->links() }}</div>
    @endif
@endsection
