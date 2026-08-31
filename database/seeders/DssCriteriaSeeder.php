<?php

namespace Database\Seeders;

use App\Models\DssCriteria;
use App\Models\DssWeight;
use Illuminate\Database\Seeder;

class DssCriteriaSeeder extends Seeder
{
    /**
     * Default weights from the thesis brief. Stored in the database (not
     * hard-coded in the scoring service) so a Super Admin can retune them
     * from the DSS Configuration screen without a code deploy.
     */
    public function run(): void
    {
        $criteria = [
            ['key' => 'budget', 'label' => 'Budget Fit', 'description' => 'How well the rent fits the renter\'s maximum monthly budget.', 'weight' => 30],
            ['key' => 'location', 'label' => 'Location Match', 'description' => 'Whether the property is in the renter\'s preferred city/municipality.', 'weight' => 20],
            ['key' => 'amenities', 'label' => 'Amenities Match', 'description' => 'Percentage of the renter\'s required amenities present.', 'weight' => 15],
            ['key' => 'property_type', 'label' => 'Property Type Match', 'description' => 'Whether the property type matches the renter\'s preference.', 'weight' => 10],
            ['key' => 'capacity', 'label' => 'Room Capacity Fit', 'description' => 'Whether the unit fits the number of occupants and bedroom needs.', 'weight' => 10],
            ['key' => 'distance', 'label' => 'Distance Fit', 'description' => 'Proximity to the renter\'s reference point (e.g. school/workplace).', 'weight' => 10],
            ['key' => 'furnishing', 'label' => 'Furnishing Match', 'description' => 'Whether furnishing status matches the renter\'s preference.', 'weight' => 5],
        ];

        foreach ($criteria as $c) {
            $criterion = DssCriteria::updateOrCreate(
                ['key' => $c['key']],
                ['label' => $c['label'], 'description' => $c['description'], 'is_active' => true]
            );

            // Only seed a weight if this criterion has no active weight yet —
            // never overwrite an admin's saved configuration on re-seed.
            if (! $criterion->activeWeight()) {
                DssWeight::create([
                    'dss_criteria_id' => $criterion->id,
                    'weight_percentage' => $c['weight'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
