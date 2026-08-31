<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Administrator', 'slug' => 'super_admin', 'description' => 'Full system access.'],
            ['name' => 'Property Owner', 'slug' => 'owner', 'description' => 'Manages own properties and tenants.'],
            ['name' => 'Tenant', 'slug' => 'tenant', 'description' => 'Searches, applies, and rents properties.'],
            ['name' => 'Property Manager', 'slug' => 'manager', 'description' => 'Manages properties assigned by an owner.'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
