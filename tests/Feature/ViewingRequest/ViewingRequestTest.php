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
            'reschedule_reason' => 'The caretaker is unavailable that day.',
        ]);

        $response->assertRedirect();
        $viewing->refresh();
        $this->assertEquals($newDate, $viewing->preferred_date->toDateString());
        $this->assertEquals('rescheduled', $viewing->status);
    }

    public function test_the_owner_form_has_no_cancel_option_and_only_asks_for_a_date_when_rescheduling(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        \App\Models\ViewingRequest::create([
            'user_id' => $tenant->id, 'property_id' => $property->id,
            'preferred_date' => now()->addDays(2), 'preferred_time' => '10:00', 'status' => 'pending',
        ]);

        $this->actingAs($owner)->get(route('owner.viewings.index'))
            ->assertOk()
            ->assertSee('value="confirmed"', false)
            ->assertSee('value="rescheduled"', false)
            ->assertDontSee('value="cancelled"', false)
            ->assertSee("status === 'rescheduled'", false);
    }

    private function pendingViewing(): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id, 'name' => 'Sunrise Apartments']);

        $viewing = \App\Models\ViewingRequest::create([
            'user_id' => $tenant->id, 'property_id' => $property->id,
            'preferred_date' => now()->addDays(2)->toDateString(), 'preferred_time' => '10:00', 'status' => 'pending',
        ]);

        return [$owner, $tenant, $viewing];
    }

    public function test_rescheduling_requires_a_reason(): void
    {
        [$owner, , $viewing] = $this->pendingViewing();

        $this->actingAs($owner)->patch(route('owner.viewings.update', $viewing), [
            'status' => 'rescheduled',
            'preferred_date' => now()->addDays(5)->toDateString(),
            'preferred_time' => '15:30',
        ])->assertSessionHasErrors('reschedule_reason');

        $this->assertSame('pending', $viewing->fresh()->status);
    }

    public function test_the_renter_sees_the_reason_on_the_page_and_in_the_floating_notification(): void
    {
        [$owner, $tenant, $viewing] = $this->pendingViewing();

        $this->actingAs($owner)->patch(route('owner.viewings.update', $viewing), [
            'status' => 'rescheduled',
            'preferred_date' => now()->addDays(5)->toDateString(),
            'preferred_time' => '15:30',
            'reschedule_reason' => 'The owner has a family emergency.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($tenant)->get(route('tenant.viewings.index'))
            ->assertOk()
            ->assertSee('The owner has a family emergency.');

        $this->actingAs($tenant)->getJson(route('alerts.index'))
            ->assertOk()
            ->assertJsonPath('alerts.0.headline', 'Your viewing was rescheduled')
            ->assertJsonPath('alerts.0.note', 'Reason: The owner has a family emergency.');
    }

    public function test_the_reason_is_cleared_when_the_viewing_is_later_confirmed(): void
    {
        [$owner, , $viewing] = $this->pendingViewing();
        $viewing->update(['status' => 'rescheduled', 'reschedule_reason' => 'Old reason here']);

        $this->actingAs($owner)->patch(route('owner.viewings.update', $viewing), ['status' => 'confirmed']);

        $this->assertNull($viewing->fresh()->reschedule_reason);
    }
}
