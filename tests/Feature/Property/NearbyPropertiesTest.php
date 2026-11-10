<?php

namespace Tests\Feature\Property;

use App\Models\Location;
use App\Models\Property;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearbyPropertiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_renter_can_manually_enter_a_city_and_see_matching_available_properties(): void
    {
        $bacoor = Location::factory()->create(['city_municipality' => 'Bacoor']);
        $imus = Location::factory()->create(['city_municipality' => 'Imus']);
        Property::factory()->create(['name' => 'Bacoor Rental', 'location_id' => $bacoor->id]);
        Property::factory()->create(['name' => 'Imus Rental', 'location_id' => $imus->id]);
        Property::factory()->pending()->create(['name' => 'Unverified Bacoor Rental', 'location_id' => $bacoor->id]);

        $this->get(route('properties.index', ['nearby' => 1, 'location' => 'Bacoor']))
            ->assertOk()
            ->assertSessionHas('nearby_search.location', 'Bacoor')
            ->assertSee('Showing available properties matching')
            ->assertSee('Bacoor Rental')
            ->assertDontSee('Imus Rental')
            ->assertDontSee('Unverified Bacoor Rental');

        $this->assertSame(0, SearchLog::count());
    }

    public function test_manually_entered_location_can_match_a_property_address(): void
    {
        Property::factory()->create([
            'name' => 'Street Match Rental',
            'address_line' => '45 Molino Boulevard',
        ]);
        Property::factory()->create([
            'name' => 'Different Street Rental',
            'address_line' => 'Main Road',
        ]);

        $this->withSession([
            'nearby_search' => ['location' => 'Molino'],
        ])
            ->get(route('properties.index', ['nearby' => 1]))
            ->assertOk()
            ->assertSee('Street Match Rental')
            ->assertDontSee('Different Street Rental');
    }

    public function test_location_search_requires_a_valid_manual_location_and_can_be_cleared(): void
    {
        $this->from(route('properties.index'))
            ->post(route('properties.nearby'), ['location' => 'x'])
            ->assertSessionHasErrors('location');

        $this->withSession([
            'nearby_search' => ['location' => 'Bacoor'],
        ])
            ->post(route('properties.nearby.clear'))
            ->assertRedirect(route('properties.index'))
            ->assertSessionMissing('nearby_search');
    }
}
