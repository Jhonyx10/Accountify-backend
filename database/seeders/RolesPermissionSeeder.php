<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            '$str'
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdminRole = Role::findOrCreate('superadmin');
        $companyAdminRole = Role::findOrCreate('company admin');
        $staffRole = Role::findOrCreate('staff');

        $superAdminRole->givePermissionTo(Permission::all());
        $companyAdminRole->givePermissionTo(Permission::all());
    }
}
