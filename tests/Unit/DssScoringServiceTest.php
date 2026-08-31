<?php

namespace Tests\Unit;

use App\Models\Amenity;
use App\Models\DssCriteria;
use App\Models\DssWeight;
use App\Models\Location;
use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\User;
use App\Services\DssScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DssScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DssScoringService();
        $this->seedDssWeights();
    }

    /**
     * Seed the exact weights from the thesis brief so the math in these
     * tests is checked against the real formula, not arbitrary numbers.
     */
    protected function seedDssWeights(): void
    {
        $weights = [
            'budget' => 30, 'location' => 20, 'amenities' => 15,
            'property_type' => 10, 'capacity' => 10, 'distance' => 10, 'furnishing' => 5,
        ];

        foreach ($weights as $key => $weight) {
            $criteria = DssCriteria::create(['key' => $key, 'label' => ucfirst($key), 'is_active' => true]);
            DssWeight::create(['dss_criteria_id' => $criteria->id, 'weight_percentage' => $weight, 'is_active' => true]);
        }
    }

    public function test_a_property_within_budget_and_matching_every_preference_scores_100(): void
    {
        $tenant = User::factory()->tenant()->create();
        $location = Location::factory()->create();
        $property = Property::factory()->create([
            'location_id' => $location->id,
            'property_type' => 'boarding_house',
        ]);
        $space = RentalSpace::factory()->create([
            'property_id' => $property->id,
            'monthly_rent' => 5000,
            'total_capacity' => 2,
            'bedrooms' => 1,
            'is_furnished' => true,
        ]);

        $results = $this->service->recommend($tenant, [
            'max_budget' => 10000,
            'location_id' => $location->id,
            'property_type' => 'boarding_house',
            'number_of_occupants' => 2,
            'required_bedrooms' => 1,
            'furnishing' => 'furnished',
        ], persist: false);

        $result = $results->firstWhere('space.id', $space->id);

        $this->assertNotNull($result);
        $this->assertEquals(100.0, $result['score']);
    }

    public function test_a_property_double_the_budget_scores_zero_on_the_budget_criterion(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'monthly_rent' => 20000]);

        $results = $this->service->recommend($tenant, ['max_budget' => 10000], persist: false);

        $result = $results->firstWhere('space.id', $space->id);

        $this->assertEquals(0, $result['breakdown']['budget']['score']);
    }

    public function test_a_property_slightly_over_budget_scores_proportionally_between_0_and_100(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();
        // 20% over a 10,000 budget → expected budget score = 100 - 20 = 80
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'monthly_rent' => 12000]);

        $results = $this->service->recommend($tenant, ['max_budget' => 10000], persist: false);

        $result = $results->firstWhere('space.id', $space->id);

        $this->assertEquals(80.0, $result['breakdown']['budget']['score']);
    }

    public function test_unspecified_preferences_stay_neutral_at_100_rather_than_penalizing(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        // No preferences given at all.
        $results = $this->service->recommend($tenant, [], persist: false);

        $result = $results->firstWhere('space.id', $space->id);

        $this->assertEquals(100.0, $result['score']);
    }

    public function test_changing_admin_weights_changes_the_computed_total_score(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();
        // 20% over a 10,000 budget → budget score = 80 (a PARTIAL score, not
        // 0 or 100 — this matters, because 0 × any weight is still 0, which
        // would make this test unable to detect a weight change at all).
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'monthly_rent' => 12000]);

        $preferences = ['max_budget' => 10000];

        $scoreBefore = $this->service->recommend($tenant, $preferences, persist: false)
            ->firstWhere('space.id', $space->id)['score'];

        // Double the budget criterion's weight from 30% to 60%.
        $budgetCriteria = DssCriteria::where('key', 'budget')->first();
        DssWeight::where('dss_criteria_id', $budgetCriteria->id)->update(['is_active' => false]);
        DssWeight::create(['dss_criteria_id' => $budgetCriteria->id, 'weight_percentage' => 60, 'is_active' => true]);

        $scoreAfter = $this->service->recommend($tenant, $preferences, persist: false)
            ->firstWhere('space.id', $space->id)['score'];

        // Budget scores 80 here (a real, partial score) so its weight
        // genuinely drives the total — proving the admin-configured weight
        // isn't just a display label, it actually changes the math.
        $this->assertNotEquals($scoreBefore, $scoreAfter);
    }

    public function test_amenities_score_reflects_the_percentage_of_required_amenities_present(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $wifi = Amenity::factory()->create(['name' => 'Wifi']);
        $cctv = Amenity::factory()->create(['name' => 'CCTV']);
        $parking = Amenity::factory()->create(['name' => 'Parking']); // NOT attached to the property

        $property->amenities()->attach([$wifi->id, $cctv->id]);

        $results = $this->service->recommend($tenant, [
            'amenity_ids' => [$wifi->id, $cctv->id, $parking->id],
        ], persist: false);

        $result = $results->firstWhere('space.id', $space->id);

        // 2 of 3 required amenities present = 66.67%
        $this->assertEqualsWithDelta(66.67, $result['breakdown']['amenities']['score'], 0.01);
    }

    public function test_only_verified_available_units_are_scored(): void
    {
        $tenant = User::factory()->tenant()->create();

        $visibleProperty = Property::factory()->create();
        $visibleSpace = RentalSpace::factory()->create(['property_id' => $visibleProperty->id]);

        $pendingProperty = Property::factory()->pending()->create();
        RentalSpace::factory()->create(['property_id' => $pendingProperty->id]);

        $results = $this->service->recommend($tenant, [], persist: false);

        $this->assertTrue($results->contains(fn ($r) => $r['space']->id === $visibleSpace->id));
        $this->assertCount(1, $results);
    }
}
