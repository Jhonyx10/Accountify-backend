<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Vender;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 2;
        $company = User::find($companyId);
        if (!$company) {
            echo "Company 2 not found.\n";
            return;
        }

        $plan = Plan::first();

        // Seed Orders
        for ($i = 1; $i <= 3; $i++) {
            Order::create([
                'order_id' => 'ORD-' . strtoupper(bin2hex(random_bytes(4))),
                'name' => $company->name,
                'email' => $company->email,
                'plan_name' => $plan ? $plan->name : 'Basic Plan',
                'plan_id' => $plan ? $plan->id : 1,
                'price' => 500.00 + ($i * 100),
                'price_currency' => 'PHP',
                'txn_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(6))),
                'payment_status' => 'succeeded',
                'payment_type' => 'Bank Transfer',
                'user_id' => $companyId,
            ]);
        }

        // Seed Customers
        for ($i = 1; $i <= 5; $i++) {
            Customer::create([
                'customer_id' => $i,
                'name' => 'Test Customer ' . $i,
                'email' => 'customer' . $i . '@company2.com',
                'password' => Hash::make('password'),
                'contact' => '123456789' . $i,
                'avatar' => '',
                'created_by' => $companyId,
                'is_active' => 1,
                'is_enable_login' => 1,
                'billing_name' => 'Customer ' . $i,
                'billing_country' => 'Philippines',
                'billing_state' => 'Metro Manila',
                'billing_city' => 'Manila',
                'billing_phone' => '12345678',
                'billing_zip' => '1000',
                'billing_address' => 'Street ' . $i,
                'shipping_name' => 'Customer ' . $i,
                'shipping_country' => 'Philippines',
                'shipping_state' => 'Metro Manila',
                'shipping_city' => 'Manila',
                'shipping_phone' => '12345678',
                'shipping_zip' => '1000',
                'shipping_address' => 'Street ' . $i,
            ]);
        }

        // Seed Vendors
        for ($i = 1; $i <= 5; $i++) {
            Vender::create([
                'vender_id' => $i,
                'name' => 'Test Vendor ' . $i,
                'email' => 'vendor' . $i . '@company2.com',
                'password' => Hash::make('password'),
                'contact' => '987654321' . $i,
                'avatar' => '',
                'created_by' => $companyId,
                'is_active' => 1,
                'is_enable_login' => 1,
                'billing_name' => 'Vendor ' . $i,
                'billing_country' => 'Philippines',
                'billing_state' => 'Metro Manila',
                'billing_city' => 'Manila',
                'billing_phone' => '12345678',
                'billing_zip' => '1000',
                'billing_address' => 'Industrial St ' . $i,
                'shipping_name' => 'Vendor ' . $i,
                'shipping_country' => 'Philippines',
                'shipping_state' => 'Metro Manila',
                'shipping_city' => 'Manila',
                'shipping_phone' => '12345678',
                'shipping_zip' => '1000',
                'shipping_address' => 'Industrial St ' . $i,
            ]);
        }

        echo "Seeding completed for Company 2.\n";
    }
}
