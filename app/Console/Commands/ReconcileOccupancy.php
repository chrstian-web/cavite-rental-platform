<?php

namespace App\Console\Commands;

use App\Models\RentalSpace;
use Illuminate\Console\Command;

/**
 * Re-syncs each rental space's occupied_capacity and status with its ACTIVE
 * contracts (the source of truth). Use when a unit has a tenant but still shows
 * as available, e.g. after importing data or editing a status by hand.
 *
 *   php artisan occupancy:reconcile --dry-run   (preview only)
 *   php artisan occupancy:reconcile             (apply)
 */
class ReconcileOccupancy extends Command
{
    protected $signature = 'occupancy:reconcile {--dry-run : Show what would change without saving}';

    protected $description = 'Sync rental space occupied_capacity/status with active contracts';

    public function handle(): int
    {
        $rows = [];

        RentalSpace::query()
            ->withCount(['rentalContracts as active_contracts_count' => fn ($q) => $q->where('status', 'active')])
            ->each(function (RentalSpace $space) use (&$rows) {
                $active = (int) $space->active_contracts_count;
                $capacity = (int) $space->total_capacity;

                $newOccupied = min($active, $capacity);

                // Only flip between available/occupied; never override reserved, maintenance or inactive.
                $newStatus = $space->status;
                if (in_array($space->status, ['available', 'occupied'], true)) {
                    $newStatus = $newOccupied >= $capacity && $capacity > 0 ? 'occupied' : 'available';
                }

                if ($newOccupied === (int) $space->occupied_capacity && $newStatus === $space->status) {
                    return;
                }

                $rows[] = [
                    $space->id,
                    $space->property_id,
                    $space->space_number,
                    "{$space->occupied_capacity}/{$capacity} · {$space->status}",
                    "{$newOccupied}/{$capacity} · {$newStatus}",
                ];

                if (! $this->option('dry-run')) {
                    $space->forceFill(['occupied_capacity' => $newOccupied, 'status' => $newStatus])->save();
                }
            });

        if ($rows === []) {
            $this->info('All rental spaces already match their active contracts.');

            return self::SUCCESS;
        }

        $this->table(['Space ID', 'Property ID', 'Unit', 'Before', 'After'], $rows);
        $this->info(count($rows).' unit(s) '.($this->option('dry-run') ? 'would be updated (dry run).' : 'updated.'));

        return self::SUCCESS;
    }
}
