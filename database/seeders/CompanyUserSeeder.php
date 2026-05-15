<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 2;
        
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => 'Company User ' . $i,
                'email' => 'user' . $i . '@company2.com',
                'password' => Hash::make('password'),
                'type' => $i == 1 ? 'admin' : 'user',
                'created_by' => $companyId,
                'is_active' => 1,
                'is_enable_login' => 1,
                'lang' => 'en',
                'mode' => 'light'
            ]);
        }

        echo "Seeded 5 users for Company ID 2.\n";
    }
}
