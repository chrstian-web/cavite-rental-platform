<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivityArchive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_archive_recent_activity_and_restore_it(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create([
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Archive');

        $this->actingAs($admin)
            ->patch(route('admin.activity.archive', ['type' => 'user_registration', 'activityId' => $owner->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_archives', [
            'admin_id' => $admin->id,
            'activity_type' => 'user_registration',
            'activity_id' => $owner->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('registered as owner');

        $this->actingAs($admin)
            ->get(route('admin.activity.archive.index'))
            ->assertOk()
            ->assertSee('Archived Activity')
            ->assertSee('registered as owner');

        $this->actingAs($admin)
            ->delete(route('admin.activity.restore', ['type' => 'user_registration', 'activityId' => $owner->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('admin_activity_archives', [
            'admin_id' => $admin->id,
            'activity_type' => 'user_registration',
            'activity_id' => $owner->id,
        ]);
    }

    public function test_non_admin_cannot_manage_activity_archive(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)
            ->patch(route('admin.activity.archive', ['type' => 'user_registration', 'activityId' => $tenant->id]))
            ->assertForbidden();

        $this->assertDatabaseCount('admin_activity_archives', 0);
    }
}
