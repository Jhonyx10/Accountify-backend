<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Superadmin (Targeting ID 1)
        $superadmin = User::firstOrCreate([
            'id' => 1, 
            'email' => 'superadmin@example.com'
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'type' => 'super admin',
            'lang' => 'en',
            'created_by' => 0,
        ]);

        $superAdminRole = Role::where('name', 'super admin')->first();
        
        if ($superAdminRole) {
            // NEW: Give the Super Admin role all existing permissions
            // This ensures getAllPermissions() actually returns a list for Vue
            $permissions = Permission::all();
            $superAdminRole->syncPermissions($permissions);

            $superadmin->assignRole($superAdminRole);
        }

        // 2. Default Company
        $company = User::firstOrCreate([
            'email' => 'company@example.com'
        ], [
            'name' => 'Default Company',
            'password' => Hash::make('password'),
            'type' => 'company',
            'lang' => 'en',
            'created_by' => $superadmin->id,
            'plan' => 1,
        ]);

        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            // Optional: If you want the default company to have specific permissions
            // $companyRole->syncPermissions(['manage users', 'view reports']);
            
            $company->assignRole($companyRole);
        }
    }
}