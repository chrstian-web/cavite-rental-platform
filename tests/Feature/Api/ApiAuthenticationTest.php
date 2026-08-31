<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::factory()->create(['slug' => 'tenant', 'name' => 'Tenant']);
    }

    public function test_a_user_can_register_via_the_api_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'API',
            'last_name' => 'Tester',
            'email' => 'apitester@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'apitester@example.com']);
    }

    public function test_a_user_can_login_via_the_api_and_receives_a_token(): void
    {
        $user = User::factory()->tenant()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password_via_the_api(): void
    {
        $user = User::factory()->tenant()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_a_protected_endpoint_rejects_requests_without_a_token(): void
    {
        $response = $this->getJson('/api/v1/applications');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_a_protected_endpoint_accepts_a_valid_token(): void
    {
        $user = User::factory()->tenant()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/auth/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_public_property_listing_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/properties');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_api_property_listing_supports_the_same_sorting_as_the_web_search(): void
    {
        \App\Models\Property::factory()->create(['name' => 'Cheap API Place', 'min_monthly_rent' => 3000]);
        \App\Models\Property::factory()->create(['name' => 'Pricey API Place', 'min_monthly_rent' => 15000]);

        $response = $this->getJson('/api/v1/properties?sort=price_low');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertEquals('Cheap API Place', $names->first());
    }
}
