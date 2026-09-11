@extends('layouts.app')

@section('title', 'My Payments')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">My Payments</h1>

    @php
        $groups = [
            ['key' => 'overdue', 'title' => 'Overdue', 'items' => $overdue, 'hint' => 'These are past due — submit a payment as soon as you can.'],
            ['key' => 'due-soon', 'title' => 'Due Soon', 'items' => $dueSoon, 'hint' => null],
            ['key' => 'pending-review', 'title' => 'Pending Review', 'items' => $pendingReview, 'hint' => "You've submitted these — your owner is reviewing them."],
            ['key' => 'paid', 'title' => 'Paid', 'items' => $paid, 'hint' => null],
            ['key' => 'failed', 'title' => 'Needs Attention', 'items' => $failed, 'hint' => 'These were rejected. Open one to see why and resubmit.'],
        ];
        $anyPayments = collect($groups)->sum(fn ($g) => $g['items']->count()) > 0;
    @endphp

    @if (! $anyPayments)
        @include('partials.empty-state', [
            'title' => 'No payments yet',
            'description' => 'Payments will appear here once your contract is active and your owner sets up your payment schedule.',
        ])
    @else
        <div class="space-y-8">
            @foreach ($groups as $group)
                @continue($group['items']->isEmpty())
                <section>
                    <div class="flex items-center gap-2 mb-3">
                        <h2 class="text-sm font-semibold text-slate-700">{{ $group['title'] }}</h2>
                        <span class="text-xs text-slate-400">({{ $group['items']->count() }})</span>
                    </div>
                    @if ($group['hint'])
                        <p class="text-xs text-slate-500 mb-3">{{ $group['hint'] }}</p>
                    @endif

                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 text-left">
                                <tr>
                                    <th class="px-4 py-3">Property</th>
                                    <th class="px-4 py-3">Amount</th>
                                    <th class="px-4 py-3">Due date</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($group['items'] as $payment)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->contract->property->name }}</td>
                                        <td class="px-4 py-3 text-slate-600">₱{{ number_format($payment->amount, 2) }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $payment->due_date->format('M j, Y') }}</td>
                                        <td class="px-4 py-3">@include('partials.payment-status-badge', ['payment' => $payment])</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('tenant.payments.show', $payment) }}" class="text-sm text-blue-600 hover:underline">
                                                {{ $payment->canBeSubmittedByTenant() ? 'Pay now' : 'View' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
