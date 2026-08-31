<?php

namespace Tests\Feature\RentalApplication;

use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_can_submit_a_rental_application(): void
    {
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $response = $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'number_of_occupants' => 1,
        ]);

        $response->assertRedirect(route('tenant.applications.index'));
        $this->assertDatabaseHas('rental_applications', [
            'user_id' => $tenant->id,
            'rental_space_id' => $space->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_tenant_cannot_view_another_tenants_application(): void
    {
        $tenantA = User::factory()->tenant()->create();
        $tenantB = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $application = RentalApplication::create([
            'user_id' => $tenantA->id,
            'property_id' => $space->property_id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($tenantB)->get("/tenant/applications/{$application->id}");

        $response->assertForbidden();
    }

    public function test_a_property_owner_can_approve_an_application_for_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patch("/owner/applications/{$application->id}/review", [
            'status' => 'approved',
            'decision_reason' => 'Looks good!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rental_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'reviewed_by' => $owner->id,
        ]);
    }

    public function test_an_owner_cannot_review_an_application_for_a_property_they_do_not_own(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $ownerA->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($ownerB)->patch("/owner/applications/{$application->id}/review", [
            'status' => 'approved',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('rental_applications', ['id' => $application->id, 'status' => 'pending']);
    }
}
