<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = User::where('email', 'company@example.com')->first();
        
        if (!$company) {
            return;
        }

        $customers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'contact' => '1234567890',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'contact' => '0987654321',
            ],
            [
                'name' => 'Acme Supplies',
                'email' => 'acme@example.com',
                'contact' => '5551234567',
            ],
            [
                'name' => 'Global Solutions',
                'email' => 'global@example.com',
                'contact' => '1112223333',
            ],
            [
                'name' => 'Tech Innovators',
                'email' => 'tech@example.com',
                'contact' => '9998887777',
            ],
        ];

        foreach ($customers as $index => $customerData) {
            Customer::firstOrCreate([
                'email' => $customerData['email'],
            ], [
                'customer_id' => $index + 1,
                'name' => $customerData['name'],
                'password' => Hash::make('password'),
                'contact' => $customerData['contact'],
                'created_by' => $company->id,
                'is_active' => 1,
                'is_enable_login' => 1,
            ]);
        }
    }
}
