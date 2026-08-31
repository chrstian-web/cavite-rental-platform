<?php

namespace Tests\Feature\ViewingRequest;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_can_request_a_property_viewing(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($tenant)->post("/tenant/properties/{$property->id}/request-viewing", [
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '14:00',
            'message' => 'Looking forward to it!',
        ]);

        $response->assertRedirect(route('tenant.viewings.index'));
        $this->assertDatabaseHas('viewing_requests', [
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'status' => 'pending',
        ]);
    }

    public function test_an_owner_can_confirm_a_viewing_request_for_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $viewing = \App\Models\ViewingRequest::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'preferred_date' => now()->addDays(2),
            'preferred_time' => '10:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patch("/owner/viewings/{$viewing->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('viewing_requests', ['id' => $viewing->id, 'status' => 'confirmed']);
    }

    /**
     * Regression test: confirming, cancelling, or completing a viewing was
     * previously wiping out its preferred_date/preferred_time with null
     * (a 500 error), because the update form always submits those fields
     * blank for any status other than "rescheduled."
     */
    public function test_confirming_a_viewing_does_not_erase_its_original_date_and_time(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $viewing = \App\Models\ViewingRequest::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patch("/owner/viewings/{$viewing->id}", [
            'status' => 'confirmed',
            'preferred_date' => null,
            'preferred_time' => null,
        ]);

        $response->assertRedirect();
        $viewing->refresh();
        $this->assertNotNull($viewing->preferred_date);
        $this->assertNotNull($viewing->preferred_time);
        $this->assertEquals('confirmed', $viewing->status);
    }

    public function test_rescheduling_a_viewing_updates_its_date_and_time(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $viewing = \App\Models\ViewingRequest::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'pending',
        ]);

        $newDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($owner)->patch("/owner/viewings/{$viewing->id}", [
            'status' => 'rescheduled',
            'preferred_date' => $newDate,
            'preferred_time' => '15:30',
        ]);

        $response->assertRedirect();
        $viewing->refresh();
        $this->assertEquals($newDate, $viewing->preferred_date->toDateString());
        $this->assertEquals('rescheduled', $viewing->status);
    }
}
