<?php

namespace Tests\Feature\Dashboard;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardCardLinksTest extends TestCase
{
    use RefreshDatabase;

    private function contract(User $owner, Property $property, string $status, string $tenantName): RentalContract
    {
        $tenant = User::factory()->tenant()->create(['first_name' => $tenantName]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        return RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 2500, 'start_date' => now(),
            'end_date' => now()->addYear(), 'status' => $status,
        ]);
    }

    private function maintenance(User $tenant, Property $property, string $status, string $description): void
    {
        MaintenanceRequest::create([
            'user_id' => $tenant->id, 'property_id' => $property->id, 'category' => 'electrical',
            'description' => $description, 'priority' => 'high', 'status' => $status,
        ]);
    }

    public function test_each_stat_card_links_to_the_matching_page(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('dashboard'))->assertOk();

        $response->assertSee('href="'.route('owner.properties.index').'"', false);
        $response->assertSee('href="'.route('owner.properties.spaces.index', $property).'"', false);
        $response->assertSee('href="'.route('owner.applications.index', ['status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('owner.contracts.index', ['status' => 'active']).'"', false);
        $response->assertSee('href="'.route('owner.maintenance.index', ['status' => 'open']).'"', false);
    }

    public function test_unit_cards_go_to_the_property_list_when_the_owner_has_several_properties(): void
    {
        $owner = User::factory()->owner()->create();
        $first = Property::factory()->create(['owner_id' => $owner->id]);
        Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('owner.properties.spaces.index', $first).'"', false);
    }

    public function test_contracts_page_can_be_filtered_to_active_contracts(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $this->contract($owner, $property, 'active', 'Aktibo');
        $this->contract($owner, $property, 'terminated', 'Tapos');

        $this->actingAs($owner)->get(route('owner.contracts.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Aktibo')
            ->assertDontSee('Tapos');

        $this->actingAs($owner)->get(route('owner.contracts.index'))
            ->assertSee('Aktibo')
            ->assertSee('Tapos');
    }

    public function test_maintenance_open_filter_matches_what_the_dashboard_counts(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $this->maintenance($tenant, $property, 'submitted', 'Leaky faucet SUBMITTED');
        $this->maintenance($tenant, $property, 'in_progress', 'Broken door IN-PROGRESS');
        $this->maintenance($tenant, $property, 'resolved', 'Old issue RESOLVED');

        $this->actingAs($owner)->get(route('owner.maintenance.index', ['status' => 'open']))
            ->assertOk()
            ->assertSee('Leaky faucet SUBMITTED')
            ->assertSee('Broken door IN-PROGRESS')
            ->assertDontSee('Old issue RESOLVED');
    }

    public function test_pending_applications_link_keeps_working_with_the_existing_status_filter(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('owner.applications.index', ['status' => 'pending']))
            ->assertOk();
    }
}
