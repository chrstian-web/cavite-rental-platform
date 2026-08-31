<?php

namespace Tests\Feature\Property;

use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_create_a_property(): void
    {
        $owner = User::factory()->owner()->create();
        $location = Location::factory()->create();

        $response = $this->actingAs($owner)->post('/owner/properties', [
            'name' => 'Test Boarding House',
            'property_type' => 'boarding_house',
            'description' => 'A nice place to stay.',
            'location_id' => $location->id,
            'address_line' => '123 Test Street',
            'min_monthly_rent' => 3000,
            'max_monthly_rent' => 6000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', [
            'name' => 'Test Boarding House',
            'owner_id' => $owner->id,
            'verification_status' => 'pending', // new listings are not auto-verified
        ]);
    }

    public function test_a_tenant_cannot_create_a_property(): void
    {
        $tenant = User::factory()->tenant()->create();
        $location = Location::factory()->create();

        $response = $this->actingAs($tenant)->post('/owner/properties', [
            'name' => 'Should Not Be Created',
            'property_type' => 'condominium',
            'location_id' => $location->id,
            'address_line' => '123 Test Street',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('properties', ['name' => 'Should Not Be Created']);
    }

    public function test_an_owner_cannot_edit_another_owners_property(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $ownerA->id]);

        $response = $this->actingAs($ownerB)->put("/owner/properties/{$property->id}", [
            'name' => 'Hijacked Name',
            'property_type' => $property->property_type,
            'location_id' => $property->location_id,
            'address_line' => $property->address_line,
            'availability_status' => 'available',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('properties', ['id' => $property->id, 'name' => 'Hijacked Name']);
    }

    public function test_an_owner_can_edit_their_own_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->put("/owner/properties/{$property->id}", [
            'name' => 'Updated Name',
            'property_type' => $property->property_type,
            'location_id' => $property->location_id,
            'address_line' => $property->address_line,
            'availability_status' => 'available',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'name' => 'Updated Name']);
    }

    public function test_super_admin_can_verify_a_pending_property(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $property = Property::factory()->pending()->create();

        $response = $this->actingAs($admin)->patch("/admin/properties/{$property->id}/verify", [
            'decision' => 'verified',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'verification_status' => 'verified']);
    }

    public function test_the_owner_is_notified_when_their_property_is_verified(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->pending()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)->patch("/admin/properties/{$property->id}/verify", [
            'decision' => 'verified',
        ]);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $owner,
            \App\Notifications\PropertyVerificationNotification::class
        );
    }
}
