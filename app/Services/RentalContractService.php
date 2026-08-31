<?php

namespace App\Services;

use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Notifications\ContractActivatedNotification;
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
            return RentalContract::create([
                ...$data,
                'rental_application_id' => $application->id,
                'user_id' => $application->user_id,
                'owner_id' => $application->property->owner_id,
                'property_id' => $application->property_id,
                'rental_space_id' => $application->rental_space_id,
                'status' => 'draft',
            ]);
        });
    }

    /**
     * Activating a contract occupies the rental space (increments occupied_capacity,
     * flips status to occupied once full) — this is the single source of truth for
     * "is this unit actually rented right now."
     */
    public function activate(RentalContract $contract): RentalContract
    {
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
