<?php

namespace Tests\Feature\MaintenanceRequest;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_can_submit_a_maintenance_request_for_an_active_contract(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $contract = RentalContract::create([
            'user_id' => $tenant->id,
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'monthly_rent' => 8000,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($tenant)->post("/tenant/contracts/{$contract->id}/maintenance", [
            'category' => 'plumbing',
            'description' => 'The faucet is leaking.',
            'priority' => 'normal',
        ]);

        $response->assertRedirect(route('tenant.maintenance.index'));
        $this->assertDatabaseHas('maintenance_requests', [
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'category' => 'plumbing',
            'status' => 'submitted',
        ]);
    }

    public function test_a_tenant_cannot_submit_a_maintenance_request_for_a_draft_contract(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $contract = RentalContract::create([
            'user_id' => $tenant->id,
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'monthly_rent' => 8000,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'draft', // not active yet
        ]);

        $response = $this->actingAs($tenant)->post("/tenant/contracts/{$contract->id}/maintenance", [
            'category' => 'plumbing',
            'description' => 'The faucet is leaking.',
            'priority' => 'normal',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('maintenance_requests', 0);
    }

    public function test_a_property_owner_can_update_a_maintenance_requests_status(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $request = \App\Models\MaintenanceRequest::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'category' => 'electrical',
            'description' => 'Outlet not working.',
            'priority' => 'high',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($owner)->patch("/owner/maintenance/{$request->id}", [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_requests', ['id' => $request->id, 'status' => 'in_progress']);
    }
}
