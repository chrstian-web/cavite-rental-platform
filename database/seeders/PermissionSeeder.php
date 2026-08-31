<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Granular permissions, grouped by module. Roles are mapped to permission
     * sets below so access can later be fine-tuned from an admin screen
     * without touching code (per the "no hard-coded business rules" brief).
     */
    public function run(): void
    {
        $permissions = [
            // module => [slug => name]
            'users' => [
                'users.manage' => 'Manage all users',
            ],
            'properties' => [
                'properties.manage_all' => 'Manage all properties (admin)',
                'properties.create' => 'Create property listings',
                'properties.update_own' => 'Update own property listings',
                'properties.delete_own' => 'Delete own property listings',
                'properties.verify' => 'Verify/approve property listings',
            ],
            'rental_spaces' => [
                'rental_spaces.manage_own' => 'Manage own rental spaces/units',
            ],
            'applications' => [
                'applications.submit' => 'Submit rental applications',
                'applications.review' => 'Approve/reject rental applications',
            ],
            'viewings' => [
                'viewings.request' => 'Request property viewings',
                'viewings.manage' => 'Manage viewing requests',
            ],
            'contracts' => [
                'contracts.manage' => 'Create/manage rental contracts',
            ],
            'payments' => [
                'payments.record' => 'Record/track payments',
                'payments.view_own' => 'View own payment history',
            ],
            'maintenance' => [
                'maintenance.submit' => 'Submit maintenance requests',
                'maintenance.manage' => 'Manage maintenance requests',
            ],
            'dss' => [
                'dss.configure' => 'Configure DSS weights and criteria',
            ],
            'reports' => [
                'reports.view' => 'View analytics and reports',
            ],
        ];

        $permissionModels = [];
        foreach ($permissions as $module => $items) {
            foreach ($items as $slug => $name) {
                $permissionModels[$slug] = Permission::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'module' => $module]
                );
            }
        }

        $roleMap = [
            'super_admin' => array_keys($permissionModels), // everything
            'owner' => [
                'properties.create', 'properties.update_own', 'properties.delete_own',
                'rental_spaces.manage_own', 'applications.review', 'viewings.manage',
                'contracts.manage', 'payments.record', 'maintenance.manage', 'reports.view',
            ],
            'manager' => [
                'rental_spaces.manage_own', 'applications.review', 'viewings.manage',
                'payments.record', 'maintenance.manage',
            ],
            'tenant' => [
                'applications.submit', 'viewings.request', 'payments.view_own', 'maintenance.submit',
            ],
        ];

        foreach ($roleMap as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }

            $ids = collect($permissionSlugs)
                ->map(fn ($slug) => $permissionModels[$slug]->id ?? null)
                ->filter()
                ->all();

            $role->permissions()->sync($ids);
        }
    }
}
