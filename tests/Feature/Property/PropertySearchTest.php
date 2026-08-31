<?php

namespace Tests\Feature\Property;

use App\Models\Location;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_and_available_properties_appear_in_public_listing(): void
    {
        $verified = Property::factory()->create(['name' => 'Visible Property']);
        Property::factory()->pending()->create(['name' => 'Hidden Pending Property']);
        Property::factory()->unavailable()->create(['name' => 'Hidden Unavailable Property']);

        $response = $this->get('/properties');

        $response->assertOk();
        $response->assertSee('Visible Property');
        $response->assertDontSee('Hidden Pending Property');
        $response->assertDontSee('Hidden Unavailable Property');
    }

    public function test_filtering_by_property_type_returns_only_matching_properties(): void
    {
        Property::factory()->create(['name' => 'Condo Listing', 'property_type' => 'condominium']);
        Property::factory()->create(['name' => 'Dorm Listing', 'property_type' => 'dormitory']);

        $response = $this->get('/properties?type=condominium');

        $response->assertSee('Condo Listing');
        $response->assertDontSee('Dorm Listing');
    }

    public function test_filtering_by_location_returns_only_matching_properties(): void
    {
        $locationA = Location::factory()->create(['city_municipality' => 'Imus']);
        $locationB = Location::factory()->create(['city_municipality' => 'Bacoor']);

        Property::factory()->create(['name' => 'Imus Property', 'location_id' => $locationA->id]);
        Property::factory()->create(['name' => 'Bacoor Property', 'location_id' => $locationB->id]);

        $response = $this->get("/properties?location_id={$locationA->id}");

        $response->assertSee('Imus Property');
        $response->assertDontSee('Bacoor Property');
    }

    public function test_filtering_by_budget_excludes_properties_outside_range(): void
    {
        Property::factory()->create(['name' => 'Affordable Place', 'min_monthly_rent' => 3000, 'max_monthly_rent' => 5000]);
        Property::factory()->create(['name' => 'Expensive Place', 'min_monthly_rent' => 20000, 'max_monthly_rent' => 30000]);

        $response = $this->get('/properties?max_rent=6000');

        $response->assertSee('Affordable Place');
        $response->assertDontSee('Expensive Place');
    }

    public function test_viewing_a_property_increments_its_view_count(): void
    {
        $property = Property::factory()->create(['views_count' => 0]);

        $this->get("/properties/{$property->slug}");

        $this->assertEquals(1, $property->fresh()->views_count);
    }

    public function test_sorting_by_lowest_price_orders_properties_ascending(): void
    {
        Property::factory()->create(['name' => 'Cheap Place', 'min_monthly_rent' => 3000, 'max_monthly_rent' => 4000]);
        Property::factory()->create(['name' => 'Pricey Place', 'min_monthly_rent' => 15000, 'max_monthly_rent' => 18000]);

        $response = $this->get('/properties?sort=price_low');

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Pricey Place'), strpos($content, 'Cheap Place'));
    }

    public function test_sorting_by_most_viewed_orders_properties_descending(): void
    {
        Property::factory()->create(['name' => 'Popular Place', 'views_count' => 500]);
        Property::factory()->create(['name' => 'Quiet Place', 'views_count' => 2]);

        $response = $this->get('/properties?sort=most_viewed');

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Quiet Place'), strpos($content, 'Popular Place'));
    }

    public function test_an_invalid_sort_value_falls_back_to_newest_without_erroring(): void
    {
        Property::factory()->create();

        $response = $this->get('/properties?sort=not-a-real-sort-option; DROP TABLE properties;--');

        $response->assertOk();
    }
}
