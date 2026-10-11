<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RentalContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the month-by-month rent schedule for a contract: one installment per
 * month of the lease, each due on the same day of the month as the start date,
 * matched against the real payment records so the tenant can see at a glance
 * which months are paid, waiting for the owner, due or overdue.
 *
 * Assumption: an advance payment that has been paid covers the first month(s)
 * of rent (amount ÷ monthly rent, at least one month).
 */
class RentScheduleService
{
    public const MAX_MONTHS = 60;

    /** How far ahead the next installment can be paid. */
    public const PAY_WINDOW_DAYS = 30;

    /**
     * @return array{rows: Collection, paid: int, total: int, next: ?array, remaining: float}
     */
    public function forContract(RentalContract $contract, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        if (! $contract->start_date || ! $contract->end_date) {
            return $this->empty();
        }

        $payments = $contract->payments()
            ->where('payment_type', Payment::TYPE_MONTHLY_RENT)
            ->get();

        $advanceMonths = $this->monthsCoveredByAdvance($contract);

        $rows = collect();
        $firstUnpaidSeen = false;

        for ($i = 0; $i < self::MAX_MONTHS; $i++) {
            $due = $contract->start_date->copy()->startOfDay()->addMonthsNoOverflow($i);

            if ($due->gt($contract->end_date)) {
                break;
            }

            $payment = $this->matchPayment($payments, $due);
            $coveredByAdvance = ! $payment && $i < $advanceMonths;

            $state = match (true) {
                $coveredByAdvance, $payment?->status === 'paid' => 'paid',
                $payment?->status === 'submitted' => 'submitted',
                $payment?->status === 'failed' => 'rejected',
                $due->lt($today) => 'overdue',
                default => 'scheduled',
            };

            // A month already submitted is waiting on the owner, so the "next" one to pay is the month after it.
            $isNext = ! $firstUnpaidSeen && ! in_array($state, ['paid', 'submitted'], true);
            if ($isNext) {
                $firstUnpaidSeen = true;

                if ($state === 'scheduled' && $due->lte($today->copy()->addDays(7))) {
                    $state = 'due';
                } elseif ($state === 'scheduled' && $due->lte($today->copy()->addDays(self::PAY_WINDOW_DAYS))) {
                    $state = 'upcoming';
                }
            }

            $rows->push([
                'number' => $i + 1,
                'month' => $due->format('Y-m'),
                'due_date' => $due,
                'amount' => (float) $contract->monthly_rent,
                'state' => $state,
                'is_next' => $isNext,
                'payment' => $payment,
                'note' => $coveredByAdvance ? 'Covered by your advance payment' : null,
                'action' => $this->actionFor($state, $isNext, $payment, $due, $today),
            ]);
        }

        $paid = $rows->where('state', 'paid')->count();

        return [
            'rows' => $rows,
            'paid' => $paid,
            'total' => $rows->count(),
            'next' => $rows->firstWhere('is_next', true),
            'remaining' => (float) $rows->where('state', '!=', 'paid')->sum('amount'),
        ];
    }

    /** What the tenant can do with this installment right now: open an existing payment, start one, or nothing. */
    protected function actionFor(string $state, bool $isNext, ?Payment $payment, Carbon $due, Carbon $today): ?string
    {
        if ($state === 'paid' || $state === 'submitted') {
            return $payment ? 'view' : null;
        }

        if ($payment) {
            return 'view';
        }

        // No payment record yet: only the next unpaid month, and only close to its due date.
        if ($isNext && $due->lte($today->copy()->addDays(self::PAY_WINDOW_DAYS))) {
            return 'start';
        }

        return null;
    }

    /** The most meaningful monthly-rent payment for that calendar month (paid > submitted > open > rejected). */
    protected function matchPayment(Collection $payments, Carbon $due): ?Payment
    {
        $rank = ['paid' => 0, 'submitted' => 1, 'pending' => 2, 'overdue' => 2, 'failed' => 3];

        return $payments
            ->filter(fn (Payment $p) => $p->due_date && $p->due_date->format('Y-m') === $due->format('Y-m'))
            ->sortBy(fn (Payment $p) => $rank[$p->status] ?? 9)
            ->first();
    }

    protected function monthsCoveredByAdvance(RentalContract $contract): int
    {
        $advance = (float) $contract->advance_payment;
        $rent = (float) $contract->monthly_rent;

        if ($advance <= 0 || $rent <= 0) {
            return 0;
        }

        $advancePaid = $contract->payments()
            ->where('payment_type', Payment::TYPE_ADVANCE_PAYMENT)
            ->where('status', 'paid')
            ->exists();

        return $advancePaid ? max(1, (int) floor($advance / $rent)) : 0;
    }

    protected function empty(): array
    {
        return ['rows' => collect(), 'paid' => 0, 'total' => 0, 'next' => null, 'remaining' => 0.0];
    }
}
