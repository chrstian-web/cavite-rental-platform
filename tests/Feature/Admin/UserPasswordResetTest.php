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
}
