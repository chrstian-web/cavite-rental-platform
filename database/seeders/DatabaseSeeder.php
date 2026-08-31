<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            LocationSeeder::class,
            AmenitySeeder::class,
            DssCriteriaSeeder::class,
        ]);
    }
}
