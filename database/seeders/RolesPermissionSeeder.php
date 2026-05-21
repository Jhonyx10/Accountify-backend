<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['view dashboard', 'manage dashboard'])->delete();

        foreach (PermissionCatalog::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $systemRoles = [
            'super admin' => PermissionCatalog::forRole('super admin'),
            'company' => PermissionCatalog::forRole('company'),
            'staff' => PermissionCatalog::forRole('staff'),
        ];

        foreach ($systemRoles as $name => $permissions) {
            $role = Role::withoutGlobalScopes()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['created_by' => 0]
            );

            if (!$role->created_by) {
                $role->update(['created_by' => 0]);
            }

            $role->syncPermissions($permissions);
        }
    }
}
