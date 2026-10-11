{{--
    Rent schedule card: next payment + every month of the lease.
    Expects $contract (RentalContract) and $schedule (RentScheduleService::forContract()).
--}}
@php
    $rows = $schedule['rows'];
    $next = $schedule['next'];
    $today = \Illuminate\Support\Carbon::today();

    $chip = fn (string $state) => match ($state) {
        'paid' => ['Paid', 'bg-green-50 text-green-700 border-green-200'],
        'submitted' => ['Waiting for owner', 'bg-blue-50 text-blue-700 border-blue-200'],
        'due' => ['Due soon', 'bg-amber-50 text-amber-700 border-amber-200'],
        'upcoming' => ['Upcoming', 'bg-slate-100 text-slate-600 border-slate-200'],
        'overdue' => ['Overdue', 'bg-red-50 text-red-700 border-red-200'],
        'rejected' => ['Payment rejected', 'bg-red-50 text-red-700 border-red-200'],
        default => ['Scheduled', 'bg-slate-100 text-slate-500 border-slate-200'],
    };
    // Rows shown before "Show all": from the month before the next unpaid one, 4 rows.
    $startAt = max(0, ($next ? $rows->search(fn ($r) => $r['month'] === $next['month']) : $rows->count() - 4) - 1);
    $preview = $rows->slice($startAt, 4)->pluck('month')->all();
@endphp

<div class="surface-card p-6" x-data="{ all: false }">
    <div class="flex items-center justify-between mb-3">
        <p class="eyebrow">Rent schedule</p>
        <span class="text-xs text-slate-400">{{ $contract->property->name ?? '' }}</span>
    </div>

    @if ($next)
        @php
            $days = $today->diffInDays($next['due_date'], false);
            [$nextLabel, $nextClass] = $chip($next['state']);
        @endphp
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-2xl font-extrabold text-slate-950">₱{{ number_format($next['amount'], 2) }}</p>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $next['state'] === 'overdue' ? 'Was due' : 'Next payment' }}
                    <strong class="text-slate-700">{{ $next['due_date']->format('M j, Y') }}</strong>
                </p>
                <p class="text-xs mt-0.5 {{ $days < 0 ? 'text-red-600 font-semibold' : 'text-slate-400' }}">
                    @if ($days < 0) {{ abs($days) }} {{ \Illuminate\Support\Str::plural('day', abs($days)) }} overdue
                    @elseif ($days === 0) Due today
                    @else In {{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }} @endif
                </p>
            </div>
            <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full border {{ $nextClass }}">{{ $nextLabel }}</span>
        </div>

        @if ($next['action'] === 'start')
            <form method="POST" action="{{ route('tenant.contracts.rent.start', [$contract, $next['month']]) }}" class="mt-4">
                @csrf
                <button type="submit" class="btn-primary btn-tenant w-full">Pay {{ $next['due_date']->format('F') }} rent</button>
            </form>
        @elseif ($next['action'] === 'view' && $next['payment'])
            <a href="{{ route('tenant.payments.show', $next['payment']) }}" class="btn-primary btn-tenant w-full mt-4 text-center block">
                {{ $next['state'] === 'submitted' ? 'View payment' : 'Pay '.$next['due_date']->format('F').' rent' }}
            </a>
        @endif
    @else
        <p class="text-lg font-extrabold text-slate-950">All rent paid ✓</p>
        <p class="text-sm text-slate-500 mt-0.5">Every month of your lease is settled.</p>
    @endif

    {{-- Progress --}}
    <div class="mt-5">
        <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
            <span><strong class="text-slate-700">{{ $schedule['paid'] }}</strong> of {{ $schedule['total'] }} months paid</span>
            <span>₱{{ number_format($schedule['remaining'], 2) }} left</span>
        </div>
        <div class="h-2 rounded-full bg-slate-200 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $schedule['total'] }}" aria-valuenow="{{ $schedule['paid'] }}">
            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-rose-500" style="width: {{ $schedule['total'] ? round($schedule['paid'] / $schedule['total'] * 100) : 0 }}%"></div>
        </div>
    </div>

    {{-- Month by month --}}
    <ul class="mt-4 divide-y divide-slate-100 text-sm">
        @foreach ($rows as $row)
            @php [$label, $class] = $chip($row['state']); @endphp
            <li x-show="all || {{ in_array($row['month'], $preview, true) ? 'true' : 'false' }}"
                class="flex items-center justify-between gap-3 py-2.5 {{ $row['is_next'] ? 'font-semibold' : '' }}">
                <div class="min-w-0">
                    <p class="text-slate-800 {{ $row['is_next'] ? '' : 'font-medium' }}">
                        <span class="text-slate-400 text-xs mr-1">#{{ $row['number'] }}</span>{{ $row['due_date']->format('M j, Y') }}
                    </p>
                    @if ($row['note'])
                        <p class="text-[11px] text-slate-400">{{ $row['note'] }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-slate-500 text-xs">₱{{ number_format($row['amount'], 0) }}</span>
                    @if ($row['payment'] && in_array($row['state'], ['paid', 'submitted', 'rejected'], true))
                        <a href="{{ route('tenant.payments.show', $row['payment']) }}" class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $class }} hover:opacity-80">{{ $label }}</a>
                    @else
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $class }}">{{ $label }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if ($rows->count() > count($preview))
        <button type="button" @click="all = !all" class="mt-3 text-sm font-bold text-rose-600 hover:underline"
                x-text="all ? 'Show less' : 'Show all {{ $rows->count() }} months'">Show all {{ $rows->count() }} months</button>
    @endif
</div>
