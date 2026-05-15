<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 2;
        
        for ($i = 1; $i <= 10; $i++) {
            Customer::create([
                'customer_id' => 100 + $i,
                'name' => 'Premium Customer ' . $i,
                'email' => 'premium.cust' . $i . '@company2.com',
                'password' => Hash::make('password'),
                'contact' => '0912345678' . $i,
                'avatar' => '',
                'created_by' => $companyId,
                'is_active' => 1,
                'is_enable_login' => 1,
                'billing_name' => 'Premium Cust ' . $i,
                'billing_country' => 'Philippines',
                'billing_state' => 'Metro Manila',
                'billing_city' => 'Makati',
                'billing_phone' => '8888777' . $i,
                'billing_zip' => '1200',
                'billing_address' => 'Ayala Ave ' . $i,
                'shipping_name' => 'Premium Cust ' . $i,
                'shipping_country' => 'Philippines',
                'shipping_state' => 'Metro Manila',
                'shipping_city' => 'Makati',
                'shipping_phone' => '8888777' . $i,
                'shipping_zip' => '1200',
                'shipping_address' => 'Ayala Ave ' . $i,
                'lang' => 'en',
                'balance' => 0.00
            ]);
        }

        echo "Seeded 10 customers for Company ID 2.\n";
    }
}
