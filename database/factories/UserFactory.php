<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'role_id' => Role::firstOrCreate(['slug' => 'tenant'], ['name' => 'Tenant'])->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    protected function withRole(string $slug, string $name): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['slug' => $slug], ['name' => $name])->id,
        ]);
    }

    public function tenant(): static
    {
        return $this->withRole('tenant', 'Tenant');
    }

    public function owner(): static
    {
        return $this->withRole('owner', 'Property Owner');
    }

    public function manager(): static
    {
        return $this->withRole('manager', 'Property Manager');
    }

    public function superAdmin(): static
    {
        return $this->withRole('super_admin', 'Super Administrator');
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
