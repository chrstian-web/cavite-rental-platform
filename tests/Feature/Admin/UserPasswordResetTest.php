<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_can_view_the_user_management_page(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->tenant()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee($user->email);
        $response->assertSee('Reset password');
    }

    public function test_a_super_admin_can_reset_another_users_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->tenant()->create(['password' => 'old-password']);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.password.update', $user),
            [
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ],
        );

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_password_confirmation_is_required(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->tenant()->create();
        $originalHash = $user->password;

        $response = $this->actingAs($admin)->patch(
            route('admin.users.password.update', $user),
            ['password' => 'NewSecurePassword123!'],
        );

        $response->assertSessionHasErrors('password');
        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_non_admin_users_cannot_reset_passwords(): void
    {
        $tenant = User::factory()->tenant()->create();
        $user = User::factory()->owner()->create();

        $response = $this->actingAs($tenant)->get(route('admin.users.password.edit', $user));

        $response->assertForbidden();
    }

    public function test_a_super_admin_can_delete_a_user_without_losing_their_records(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->tenant()->create();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_an_admin_cannot_delete_themselves_or_another_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('delete');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $other))->assertSessionHasErrors('delete');

        $this->assertNotSoftDeleted('users', ['id' => $other->id]);
    }

    public function test_a_non_admin_cannot_delete_users(): void
    {
        $tenant = User::factory()->tenant()->create();
        $victim = User::factory()->tenant()->create();

        $this->actingAs($tenant)->delete(route('admin.users.destroy', $victim))->assertForbidden();
    }

    public function test_a_deleted_user_can_be_restored_from_the_deleted_users_page(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->tenant()->create();
        $user->delete();

        $this->actingAs($admin)->get(route('admin.users.deleted'))->assertOk()->assertSee($user->email);

        $this->actingAs($admin)->patch(route('admin.users.restore', $user->id))
            ->assertRedirect(route('admin.users.deleted'));

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_only_a_super_admin_can_restore_users(): void
    {
        $tenant = User::factory()->tenant()->create();
        $victim = User::factory()->tenant()->create();
        $victim->delete();

        $this->actingAs($tenant)->patch(route('admin.users.restore', $victim->id))->assertForbidden();
    }
}
