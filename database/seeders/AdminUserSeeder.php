<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'ChangeMe123!';

    public function run(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->firstOrFail();

        // Local development keeps the old defaults. On a public server set
        // ADMIN_EMAIL and ADMIN_PASSWORD, or the seeder refuses to run.
        $email = env('ADMIN_EMAIL', 'admin@caviterentals.test');
        $password = env('ADMIN_PASSWORD', self::DEFAULT_PASSWORD);

        if (app()->environment('production') && $password === self::DEFAULT_PASSWORD) {
            throw new \RuntimeException('Set ADMIN_EMAIL and a strong ADMIN_PASSWORD before seeding in production.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'role_id' => $superAdminRole->id,
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'phone' => null,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
    }
}
