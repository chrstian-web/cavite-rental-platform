<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::factory()->create(['slug' => 'tenant', 'name' => 'Tenant']);
        Role::factory()->create(['slug' => 'owner', 'name' => 'Property Owner']);
    }

    public function test_a_new_user_can_register_as_a_tenant(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tenant',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('tenant', $user->role->slug);
    }

    public function test_registration_rejects_an_attempt_to_self_register_as_super_admin(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Sneaky',
            'last_name' => 'User',
            'email' => 'sneaky@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'super_admin', // not in the allowed list
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan2@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DoesNotMatch!',
            'role' => 'tenant',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->tenant()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'Another',
            'last_name' => 'User',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tenant',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_a_phone_number_shorter_than_11_digits_is_rejected(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juanphone@example.com',
            'phone' => '0917123456', // 10 digits, one short
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tenant',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_a_phone_number_with_non_digit_characters_is_rejected(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juanphone2@example.com',
            'phone' => '0917-123-4567', // dashes not allowed
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tenant',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_a_valid_11_digit_phone_number_is_accepted(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juanphone3@example.com',
            'phone' => '09171234567', // exactly 11 digits
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tenant',
        ]);

        $response->assertSessionDoesntHaveErrors('phone');
        $this->assertDatabaseHas('users', ['email' => 'juanphone3@example.com', 'phone' => '09171234567']);
    }
}
