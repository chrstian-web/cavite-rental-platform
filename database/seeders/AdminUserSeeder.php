<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@caviterentals.test'],
            [
                'role_id' => $superAdminRole->id,
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'phone' => null,
                'password' => Hash::make('ChangeMe123!'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
    }
}
