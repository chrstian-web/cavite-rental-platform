<?php

namespace Tests\Feature\Admin;

use App\Models\OwnerVerification;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_pending_queue_and_user_breakdown(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create();
        User::factory()->manager()->create();
        User::factory()->tenant()->count(2)->create();

        Property::factory()->pending()->create(['owner_id' => $owner->id]);
        OwnerVerification::create([
            'user_id' => $owner->id,
            'status' => 'needs_review',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Owner verifications to review')
            ->assertSee('Properties awaiting verification')
            ->assertSee('Recent Activity')
            ->assertViewHas('pendingOwnerVerifications', 1)
            ->assertViewHas('pendingPropertyVerifications', 1)
            ->assertViewHas('totalOwners', 1)
            ->assertViewHas('totalManagers', 1)
            ->assertViewHas('totalTenants', 2);
    }

    public function test_admin_navbar_does_not_show_owner_only_menus(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('My Properties')
            ->assertSee('Owner Verifications');
    }

    public function test_verifications_of_deleted_owners_do_not_break_the_list_or_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create();

        OwnerVerification::create([
            'user_id' => $owner->id,
            'status' => 'needs_review',
            'submitted_at' => now(),
        ]);
        $owner->delete();

        $this->actingAs($admin)->get('/admin/owner-verifications')->assertOk();
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertViewHas('pendingOwnerVerifications', 0);
    }
}
