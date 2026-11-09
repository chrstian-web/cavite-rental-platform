<?php

namespace Tests\Feature\RentalSpace;

use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalSpaceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_changes_to_a_rental_space(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Test Property',
        ]);
        $space = RentalSpace::factory()->create([
            'property_id' => $property->id,
            'space_number' => 'A-101',
            'space_type' => 'Studio',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'total_capacity' => 1,
            'monthly_rent' => 12000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($owner)->put(
            route('owner.properties.spaces.update', [$property, $space]),
            [
                'space_number' => 'A-102',
                'space_type' => 'Studio Plus',
                'bedrooms' => 2,
                'bathrooms' => 1,
                'total_capacity' => 2,
                'floor_area_sqm' => '35.50',
                'is_furnished' => '1',
                'monthly_rent' => '13500',
                'security_deposit' => '13500',
                'advance_payment' => '13500',
                'status' => 'available',
                'utilities_included' => ['water', 'wifi'],
                'attributes' => [],
            ],
        );

        $response->assertRedirect(route('owner.properties.spaces.index', $property));
        $response->assertSessionHas('status', 'Rental space updated.');

        $this->assertDatabaseHas('rental_spaces', [
            'id' => $space->id,
            'space_number' => 'A-102',
            'space_type' => 'Studio Plus',
            'bedrooms' => 2,
            'total_capacity' => 2,
            'is_furnished' => 1,
        ]);
    }
}
