@extends('layouts.app')

@section('title', 'Payments')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Payments</h1>
        @if ($pendingReviewCount > 0)
            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">
                {{ $pendingReviewCount }} awaiting review
            </span>
        @endif
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ([
            'submitted' => 'Pending Review',
            'pending' => 'Due / Needs Resubmission',
            'paid' => 'Paid',
            'failed' => 'Rejected',
            'overdue' => 'Overdue',
            'all' => 'All',
        ] as $key => $label)
            <a href="{{ route('owner.payments.index', ['status' => $key]) }}"
               class="text-xs font-medium px-3 py-1.5 rounded-full border {{ $status === $key ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($payments->isEmpty())
        @include('partials.empty-state', [
            'title' => 'Nothing here',
            'description' => 'No payments match this filter right now.',
        ])
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Tenant</th>
                        <th class="px-4 py-3">Property</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 text-slate-900">{{ $payment->tenant->first_name }} {{ $payment->tenant->last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->contract->property->name }}</td>
                            <td class="px-4 py-3 text-slate-600">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->submitted_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3">@include('partials.payment-status-badge', ['payment' => $payment])</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('owner.payments.show', $payment) }}" class="text-sm text-blue-600 hover:underline">
                                    {{ $payment->canBeReviewed() ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $payments->links() }}</div>
    @endif
@endsection
