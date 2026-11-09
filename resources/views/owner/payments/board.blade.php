@extends('layouts.app')

@section('title', 'Payment Board')

@php
    $peso = fn ($n) => '₱'.number_format($n, 0);
    $columnMeta = [
        'overdue'    => ['label' => 'Overdue',          'card' => 'bg-red-700 text-white',        'dot' => 'bg-red-600',    'sub' => 'text-red-100'],
        'to_collect' => ['label' => 'To collect',       'card' => 'bg-amber-300 text-amber-950',  'dot' => 'bg-amber-400',  'sub' => 'text-amber-900/70'],
        'paid'       => ['label' => 'Paid',             'card' => 'bg-emerald-800 text-white',    'dot' => 'bg-emerald-700','sub' => 'text-emerald-100'],
    ];
    $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $month);
@endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Payment board</h1>
            <p class="text-sm text-slate-500 mt-1">See who has paid, who still owes, and who is late.</p>
        </div>
        <div class="inline-flex rounded-full bg-slate-100 border border-slate-200 p-1 text-sm">
            <span class="px-4 py-1.5 rounded-full bg-white shadow-sm font-medium text-slate-900">Board</span>
            <a href="{{ route('owner.payments.index') }}" class="px-4 py-1.5 rounded-full text-slate-500 hover:text-slate-900">List</a>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <div class="surface-card p-4">
            <div class="text-xs text-slate-500">Collected in {{ $monthDate->format('F') }}</div>
            <div class="text-2xl font-semibold text-emerald-500 mt-1">{{ $peso($stats['collected']) }}</div>
            <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                <div class="h-full bg-emerald-500" style="width: {{ $stats['rate'] }}%"></div>
            </div>
            <div class="text-xs text-slate-500 mt-1">{{ $stats['rate'] }}% of {{ $peso($stats['expected']) }} expected</div>
        </div>
        <div class="surface-card p-4">
            <div class="text-xs text-slate-500">Still to collect</div>
            <div class="text-2xl font-semibold text-amber-500 mt-1">{{ $peso($columns['to_collect']->sum('amount')) }}</div>
            <div class="text-xs text-slate-500 mt-3">{{ $columns['to_collect']->count() }} not yet paid</div>
        </div>
        <div class="surface-card p-4">
            <div class="text-xs text-slate-500">Overdue (all months)</div>
            <div class="text-2xl font-semibold text-red-500 mt-1">{{ $peso($columns['overdue']->sum('amount')) }}</div>
            <div class="text-xs text-slate-500 mt-3">{{ $columns['overdue']->count() }} late payment(s)</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search tenant…"
               class="w-56 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/20">
        <select name="property_id" onchange="this.form.submit()"
                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm">
            <option value="">All properties</option>
            @foreach ($properties as $p)
                <option value="{{ $p->id }}" @selected((string) $propertyId === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <div class="ml-auto inline-flex items-center gap-1 text-sm">
            <a href="{{ route('owner.payments.board', ['month' => $monthDate->copy()->subMonth()->format('Y-m'), 'q' => $search, 'property_id' => $propertyId]) }}"
               class="px-3 py-2 rounded-full border border-slate-200 bg-white hover:border-slate-400" aria-label="Previous month">‹</a>
            <span class="px-4 py-2 rounded-full bg-slate-900 text-white font-medium">{{ $monthDate->format('F Y') }}</span>
            <a href="{{ route('owner.payments.board', ['month' => $monthDate->copy()->addMonth()->format('Y-m'), 'q' => $search, 'property_id' => $propertyId]) }}"
               class="px-3 py-2 rounded-full border border-slate-200 bg-white hover:border-slate-400" aria-label="Next month">›</a>
        </div>
    </form>

    {{-- Board --}}
    <div class="flex gap-4 overflow-x-auto pb-4 -mx-4 px-4 snap-x">
        @foreach ($columnMeta as $key => $meta)
            @php $items = $columns[$key]; @endphp
            <section class="w-72 shrink-0 snap-start rounded-2xl bg-slate-100 border border-slate-200 p-3 flex flex-col">
                <header class="flex items-center gap-2 px-1 pb-3">
                    <span class="h-2.5 w-2.5 rounded-full {{ $meta['dot'] }}"></span>
                    <h2 class="text-sm font-semibold text-slate-900">{{ $meta['label'] }}</h2>
                    <span class="text-xs text-slate-500 bg-white border border-slate-200 rounded-full px-2 py-0.5">{{ $items->count() }}</span>
                    <span class="ml-auto text-xs font-medium text-slate-500">{{ $peso($items->sum('amount')) }}</span>
                </header>

                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                    @forelse ($items as $payment)
                        @php
                            $days = $payment->due_date->startOfDay()->diffInDays(today(), false);
                            $topLine = match ($key) {
                                'paid'     => 'Paid '.($payment->paid_at ?? $payment->payment_date)?->format('M j'),
                                'overdue'  => 'Due '.$payment->due_date->format('M j').' · '.max(1, (int) $days).' day(s) late',
                                default    => 'Due '.$payment->due_date->format('M j'),
                            };
                        @endphp
                        <a href="{{ route('owner.payments.show', $payment) }}"
                           class="block rounded-2xl p-4 {{ $meta['card'] }} focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2">
                            <div class="text-xs {{ $meta['sub'] }}">{{ $topLine }}</div>
                            <div class="mt-2 font-semibold leading-snug">{{ $payment->tenant->first_name }} {{ $payment->tenant->last_name }}</div>
                            <div class="text-xs {{ $meta['sub'] }}">
                                {{ $payment->contract->property->name }}@if ($payment->contract->rentalSpace) , Unit {{ $payment->contract->rentalSpace->space_number }}@endif
                            </div>
                            <div class="mt-4 flex items-end justify-between gap-2">
                                <div class="text-xl font-semibold">₱{{ number_format($payment->amount, 0) }}</div>
                                <div class="text-right text-xs {{ $meta['sub'] }}">
                                    <div>{{ $payment->typeLabel() }}</div>
                                    @if ($key === 'paid')
                                        <div>{{ $payment->isOnline() ? 'Online' : str($payment->payment_method ?? 'manual')->replace('_', ' ')->title() }}</div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-xs text-slate-400">Nothing here</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
@endsection
