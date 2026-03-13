<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Superadmin
        $superadmin = User::firstOrCreate([
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
            $superadmin->assignRole($superAdminRole);
        }

        // Default Company
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
            $company->assignRole($companyRole);
        }
    }
}
