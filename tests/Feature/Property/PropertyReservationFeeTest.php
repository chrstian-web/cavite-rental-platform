<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyReservationFeeTest extends TestCase
{
    use RefreshDatabase;

    private function applicationFor(Property $property, User $tenant, array $overrides = []): RentalApplication
    {
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        return RentalApplication::create($overrides + [
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_the_owner_edit_form_shows_and_saves_the_reservation_fee(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id, 'reservation_fee' => 1500]);

        $this->actingAs($owner)->get(route('owner.properties.edit', $property))
            ->assertOk()
            ->assertSee('Reservation fee')
            ->assertSee('name="reservation_fee" value="1500.00"', false);

        $this->actingAs($owner)->put(route('owner.properties.update', $property), [
            'name' => $property->name,
            'property_type' => $property->property_type,
            'location_id' => $property->location_id,
            'address_line' => $property->address_line,
            'availability_status' => 'available',
            'reservation_fee' => 2000,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2000.00', $property->fresh()->reservation_fee);
    }

    public function test_the_reservation_fee_cannot_be_negative(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->put(route('owner.properties.update', $property), [
            'name' => $property->name,
            'property_type' => $property->property_type,
            'location_id' => $property->location_id,
            'address_line' => $property->address_line,
            'availability_status' => 'available',
            'reservation_fee' => -5,
        ])->assertSessionHasErrors('reservation_fee');
    }

    public function test_the_renter_sees_the_reservation_fee_on_the_rental_space_page(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['reservation_fee' => 1500]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'status' => 'available']);

        $this->actingAs($tenant)->get(route('tenant.spaces.show', $space))
            ->assertOk()
            ->assertSee('Reservation fee')
            ->assertSee('1,500.00');
    }

    public function test_a_property_without_a_reservation_fee_says_so(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['reservation_fee' => null]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'status' => 'available']);

        $this->actingAs($tenant)->get(route('tenant.spaces.show', $space))
            ->assertOk()
            ->assertSee('No reservation fee');
    }

    public function test_a_submitted_application_keeps_the_fee_the_property_had_at_that_time(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['reservation_fee' => 1500]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'status' => 'available']);

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'lease_end_date' => now()->addYear()->toDateString(),
            'number_of_occupants' => 1,
            'employment_status' => 'employed',
            'documents' => [
                'valid_id_1' => UploadedFile::fake()->create('id1.jpg', 100, 'image/jpeg'),
                'proof_of_income' => UploadedFile::fake()->create('income.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect(route('tenant.applications.index'));

        $property->update(['reservation_fee' => 9999]);

        $application = RentalApplication::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame('1500.00', $application->reservation_fee);
        $this->assertNull($application->reservation_offer_sent_at);
    }

    public function test_the_owner_approval_form_is_prefilled_with_the_property_fee(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id, 'reservation_fee' => 1500]);
        $application = $this->applicationFor($property, $tenant, ['reservation_fee' => 1500]);

        $this->actingAs($owner)->get(route('owner.applications.show', $application))
            ->assertOk()
            ->assertSee('name="reservation_fee"', false)
            ->assertSee('value="1500.00"', false);
    }

    public function test_older_applications_without_a_saved_fee_fall_back_to_the_property_fee(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id, 'reservation_fee' => 1800]);
        $application = $this->applicationFor($property, $tenant);

        $this->actingAs($owner)->get(route('owner.applications.show', $application))
            ->assertOk()
            ->assertSee('value="1800.00"', false);
    }
}
