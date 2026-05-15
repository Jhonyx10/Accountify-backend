<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class ExtraOrderSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 2;
        $company = User::find($companyId);
        $plan = Plan::first();
        
        for ($i = 1; $i <= 5; $i++) {
            Order::create([
                'order_id' => 'ORD-EXT-' . strtoupper(bin2hex(random_bytes(4))),
                'name' => $company->name,
                'email' => $company->email,
                'plan_name' => $plan ? $plan->name : 'Basic Plan',
                'plan_id' => $plan ? $plan->id : 1,
                'price' => 200.00 + ($i * 50),
                'price_currency' => 'PHP',
                'txn_id' => 'TXN-EXT-' . strtoupper(bin2hex(random_bytes(6))),
                'payment_status' => 'succeeded',
                'payment_type' => 'Credit Card',
                'user_id' => $companyId,
            ]);
        }

        echo "Seeded 5 extra orders for Company ID 2.\n";
    }
}
