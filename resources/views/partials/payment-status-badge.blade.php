{{-- Variables: $payment (required) --}}
@php
    $label = match(true) {
        $payment->isPastDue() => 'Overdue',
        $payment->status === 'pending' => 'Due Soon',
        $payment->status === 'submitted' => 'Pending Review',
        default => (string) str($payment->status)->title(),
    };
    $classes = match(true) {
        $payment->isPastDue() => 'bg-red-50 text-red-700',
        $payment->status === 'paid' => 'bg-green-50 text-green-700',
        $payment->status === 'failed' => 'bg-red-50 text-red-700',
        $payment->status === 'submitted' => 'bg-blue-50 text-blue-700',
        default => 'bg-amber-50 text-amber-700',
    };
@endphp
<span class="text-xs font-medium px-2 py-1 rounded-full {{ $classes }}">{{ $label }}</span>
