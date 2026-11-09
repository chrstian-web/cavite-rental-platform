<?php

namespace Tests\Feature\Property;

use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            'latitude' => 14.3212345,
            'longitude' => 120.9123456,
            'min_monthly_rent' => 3000,
            'max_monthly_rent' => 6000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', [
            'name' => 'Test Boarding House',
            'owner_id' => $owner->id,
            'verification_status' => 'pending', // new listings are not auto-verified
            'latitude' => 14.3212345,
            'longitude' => 120.9123456,
        ]);
    }

    public function test_owner_property_form_omits_the_map_picker_but_keeps_location_fields(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/owner/properties/create')
            ->assertOk()
            ->assertSee('City / Municipality')
            ->assertSee('Street address')
            ->assertSee('e.g. 123 Rizal Street, Brgy. San Agustin')
            ->assertSee('property-image-previews')
            ->assertSee('The first upload becomes the cover if the property does not already have one.')
            ->assertDontSee('Set exact property location')
            ->assertDontSee('property-location-map')
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false);
    }

    public function test_owner_can_search_an_address_for_map_picker_results(): void
    {
        Cache::forget('maps:nominatim:request-window');
        Http::fake(fn (ClientRequest $request) => Http::response([
            ['display_name' => '123 Test Street, Amadeo, Cavite, Philippines', 'lat' => '14.3212345', 'lon' => '120.9123456'],
        ], 200));

        $owner = User::factory()->owner()->create();
        $response = $this->actingAs($owner)->postJson('/owner/map/search', [
            'q' => '123 Test Street, Amadeo, Cavite, Philippines',
        ]);

        $response->assertOk()->assertJsonPath('results.0.label', '123 Test Street, Amadeo, Cavite, Philippines')
            ->assertJsonPath('results.0.latitude', 14.3212345)
            ->assertJsonPath('results.0.longitude', 120.9123456);

        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'nominatim.openstreetmap.org/search')
            && $request->hasHeader('User-Agent', 'CaviteRentals/1.0 (+'.config('app.url').')')
            && $request['countrycodes'] === 'ph');
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

    public function test_edit_page_explicitly_associates_save_button_with_property_form(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->get(route('owner.properties.edit', $property))
            ->assertOk()
            ->assertSee('id="property-update-form"', false)
            ->assertSee('form="property-update-form"', false);
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
