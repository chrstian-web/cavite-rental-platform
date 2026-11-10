<?php

namespace Tests\Feature\RentalSpace;

use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalSpaceDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    private function openSpace(array $overrides = []): RentalSpace
    {
        $property = Property::factory()->create([
            'name' => 'CWD Dormitory',
            'description' => 'No overnight guests. Rent is due on the first day of each month.',
        ]);

        return RentalSpace::factory()->create($overrides + [
            'property_id' => $property->id,
            'space_number' => 'Room 7',
            'space_type' => 'Shared',
            'monthly_rent' => 3500,
            'security_deposit' => 1000,
            'advance_payment' => 3500,
            'total_capacity' => 4,
            'occupied_capacity' => 0,
            'status' => 'available',
            'utilities_included' => ['water', 'wifi'],
            'attributes' => ['gender_restriction' => 'female', 'curfew_time' => '22:00'],
        ]);
    }

    public function test_apply_leads_to_a_page_with_the_room_details_viewing_box_and_map(): void
    {
        $space = $this->openSpace();
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->get(route('tenant.spaces.show', $space))
            ->assertOk()
            ->assertSee('Room 7')
            ->assertSee('3,500')
            ->assertSee('Female only')
            ->assertSee('10:00 PM')
            ->assertSee('Water')
            ->assertSee('Wifi')
            ->assertSee('Terms and Conditions')
            ->assertSee('No overnight guests. Rent is due on the first day of each month.')
            ->assertSee('Request a Viewing')
            ->assertSee(route('tenant.viewings.store', $space->property), false)
            ->assertSee('name="rental_space_id" value="'.$space->id.'"', false)
            ->assertSee('maps.google.com/maps', false)
            ->assertSee(route('tenant.applications.create', $space), false);
    }

    public function test_a_viewing_requested_from_the_room_page_is_tied_to_that_room(): void
    {
        $space = $this->openSpace();
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->post(route('tenant.viewings.store', $space->property), [
            'rental_space_id' => $space->id,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '15:00',
        ])->assertRedirect(route('tenant.viewings.index'));

        $this->assertDatabaseHas('viewing_requests', [
            'user_id' => $tenant->id,
            'property_id' => $space->property_id,
            'rental_space_id' => $space->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_full_room_redirects_back_to_the_property_page(): void
    {
        $space = $this->openSpace(['total_capacity' => 1, 'occupied_capacity' => 1]);
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->get(route('tenant.spaces.show', $space))
            ->assertRedirect(route('properties.show', $space->property->slug));
    }

    public function test_owners_and_guests_cannot_open_the_tenant_room_page(): void
    {
        $space = $this->openSpace();

        $this->get(route('tenant.spaces.show', $space))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('tenant.spaces.show', $space))->assertForbidden();
    }
}
