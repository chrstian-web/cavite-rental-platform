<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_cannot_access_owner_only_routes(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant)->get('/owner/properties');

        $response->assertForbidden();
    }

    public function test_an_owner_can_access_owner_routes(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->get('/owner/properties');

        $response->assertOk();
    }

    public function test_a_tenant_cannot_access_admin_only_routes(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant)->get('/admin/properties');

        $response->assertForbidden();
    }

    public function test_a_super_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get('/admin/properties');

        $response->assertOk();
    }

    public function test_a_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
