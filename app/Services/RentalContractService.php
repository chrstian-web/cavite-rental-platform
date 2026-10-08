<?php

namespace App\Services;

use App\Models\RentalApplication;
use App\Models\Payment;
use App\Models\RentalContract;
use App\Notifications\ContractActivatedNotification;
use App\Notifications\DownPaymentRequestedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RentalContractService
{
    /**
     * A contract can only be created from an approved application, and only once.
     * Starts as 'draft' so an owner can review terms before the tenancy goes live.
     */
    public function createFromApplication(RentalApplication $application, array $data): RentalContract
    {
        abort_unless($application->status === 'approved', 422, 'Only approved applications can become a contract.');
        abort_if($application->contract()->exists(), 422, 'A contract already exists for this application.');

        return DB::transaction(function () use ($application, $data) {
            $contract = RentalContract::create([
                ...$data,
                'rental_application_id' => $application->id,
                'user_id' => $application->user_id,
                'owner_id' => $application->property->owner_id,
                'property_id' => $application->property_id,
                'rental_space_id' => $application->rental_space_id,
                'status' => 'draft',
            ]);

            // The tenant must pay the security deposit / advance before move-in.
            $this->ensureDownPayments($contract);

            return $contract;
        });
    }

    /**
     * Makes sure every required down payment (security deposit, advance) has a
     * payable record. Safe to call repeatedly: it only creates what is missing,
     * and re-issues one if the previous attempt was rejected ('failed').
     */
    public function ensureDownPayments(RentalContract $contract): void
    {
        $created = [];

        foreach ($contract->downPaymentRequirements() as $type => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $hasLive = $contract->payments()
                ->where('payment_type', $type)
                ->whereIn('status', ['pending', 'submitted', 'paid', 'overdue'])
                ->exists();

            if ($hasLive) {
                continue;
            }

            $created[] = $contract->payments()->create([
                'user_id' => $contract->user_id,
                'payment_type' => $type,
                'amount' => $amount,
                'due_date' => $this->downPaymentDueDate($contract),
                'status' => 'pending',
                'notes' => 'Required before move-in.',
            ]);
        }

        if ($created !== []) {
            $contract->tenant->notify(new DownPaymentRequestedNotification($contract));
        }
    }

    /** One day before move-in, but never in the past. */
    protected function downPaymentDueDate(RentalContract $contract)
    {
        return Carbon::today()->max($contract->start_date->copy()->subDay());
    }

    /**
     * Activating a contract occupies the rental space (increments occupied_capacity,
     * flips status to occupied once full) — this is the single source of truth for
     * "is this unit actually rented right now."
     */
    public function activate(RentalContract $contract): RentalContract
    {
        // Airbnb-style: the unit is only handed over once the down payment is in.
        abort_unless($contract->hasSettledDownPayments(), 422, $contract->downPaymentBlockedMessage());

        return DB::transaction(function () use ($contract) {
            $contract->update(['status' => 'active']);

            $space = $contract->rentalSpace;
            $space->increment('occupied_capacity');
            if ($space->occupied_capacity >= $space->total_capacity) {
                $space->update(['status' => 'occupied']);
            }

            $contract->tenant->notify(new ContractActivatedNotification($contract));

            return $contract->fresh();
        });
    }

    /**
     * Terminating or expiring a contract frees up the space's capacity again.
     */
    public function end(RentalContract $contract, string $status): RentalContract
    {
        abort_unless(in_array($status, ['expired', 'terminated'], true), 422);

        return DB::transaction(function () use ($contract, $status) {
            $wasActive = $contract->status === 'active';
            $contract->update(['status' => $status]);

            if ($wasActive) {
                $space = $contract->rentalSpace;
                $space->decrement('occupied_capacity');
                if ($space->status === 'occupied') {
                    $space->update(['status' => 'available']);
                }
            }

            return $contract->fresh();
        });
    }
}
