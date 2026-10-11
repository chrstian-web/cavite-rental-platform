<?php

namespace Tests\Feature\RentalSpace;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedRoomCapacityTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Property, 1: RentalSpace, 2: User} */
    private function sharedRoom(int $capacity, int $activeTenants): array
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id, 'name' => 'Dorm House']);
        $space = RentalSpace::factory()->create([
            'property_id' => $property->id, 'space_number' => 'room1101',
            'total_capacity' => $capacity, 'occupied_capacity' => $activeTenants, 'status' => 'available',
        ]);

        for ($i = 0; $i < $activeTenants; $i++) {
            RentalContract::create([
                'user_id' => User::factory()->tenant()->create()->id, 'owner_id' => $owner->id,
                'property_id' => $property->id, 'rental_space_id' => $space->id, 'monthly_rent' => 2500,
                'start_date' => now(), 'end_date' => now()->addYear(), 'status' => 'active',
            ]);
        }

        return [$property, $space, User::factory()->tenant()->create()];
    }

    public function test_a_shared_room_with_free_beds_is_still_open_and_shows_apply(): void
    {
        [$property, $space, $tenant] = $this->sharedRoom(8, 1);

        $this->assertSame(7, $space->spotsLeft());
        $this->assertTrue($space->acceptsApplications());

        $this->actingAs($tenant)->get(route('properties.show', $property->slug))
            ->assertOk()
            ->assertSee('1/8 taken')
            ->assertSee('7 left')
            ->assertSee(route('tenant.applications.create', $space), false)
            ->assertDontSee('Full');
    }

    public function test_a_full_room_shows_full_and_no_apply_link(): void
    {
        [$property, $space, $tenant] = $this->sharedRoom(2, 2);

        $this->assertTrue($space->isFull());

        $this->actingAs($tenant)->get(route('properties.show', $property->slug))
            ->assertOk()
            ->assertSee('2/2 taken')
            ->assertDontSee(route('tenant.applications.create', $space), false);
    }

    public function test_a_full_room_cannot_be_applied_to_through_a_direct_link(): void
    {
        [$property, $space, $tenant] = $this->sharedRoom(1, 1);

        $this->actingAs($tenant)->get(route('tenant.applications.create', $space))
            ->assertRedirect(route('properties.show', $property->slug));
    }
}
