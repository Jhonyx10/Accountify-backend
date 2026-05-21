<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::firstOrCreate([
            'id' => 1,
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'type' => 'super admin',
            'lang' => 'en',
            'created_by' => 0,
        ]);

        $superAdminRole = Role::withoutGlobalScopes()->where('name', 'super admin')->first();

        if ($superAdminRole) {
            $superadmin->syncRoles([$superAdminRole]);
        }

        $company = User::firstOrCreate([
            'email' => 'company@example.com',
        ], [
            'name' => 'Default Company',
            'password' => Hash::make('password'),
            'type' => 'company',
            'lang' => 'en',
            'created_by' => $superadmin->id,
            'plan' => 1,
        ]);

        $companyRole = Role::withoutGlobalScopes()
            ->whereIn('name', ['company', 'Company', 'company admin'])
            ->first();

        if ($companyRole) {
            $company->syncRoles([$companyRole]);
        }
    }
}
