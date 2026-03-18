<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vender;
use App\Models\Invoice;
use App\Models\Tax;
use App\Models\ProductService;
use Illuminate\Support\Facades\Auth;

class AccountifyProductionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Main Company
        $company = Company::firstOrCreate(
            ['slug' => 'main-corp'],
            ['name' => 'Main Corporation', 'status' => 'active']
        );

        // 2. Create and Assign a User
        $user = User::firstOrCreate(
            ['email' => 'admin@maincorp.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'company_id' => $company->id
            ]
        );

        // 3. IMPORTANT: Login the user so the Trait can grab the company_id automatically
        Auth::login($user);

        // --- SALES CATEGORY ---
        $customer = Customer::create([
            'name' => 'John Doe Services',
            'email' => 'john@example.com'
        ]);

        // --- PURCHASES CATEGORY ---
        $vender = Vender::create([
            'name' => 'Global Suppliers Ltd',
            'email' => 'supply@global.com'
        ]);

        // --- SETTINGS CATEGORY ---
        $tax = Tax::create([
            'name' => 'VAT',
            'rate' => 12
        ]);

        $product = ProductService::create([
            'name' => 'Web Development Consultation',
            'sale_price' => 1500.00,
            'type' => 'service'
        ]);

        // --- FINANCIALS CATEGORY ---
        Invoice::create([
            'invoice_number' => 'INV-0001',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'amount' => 1500.00,
            // company_id is NOT needed here; the Trait injects it!
        ]);

        $this->command->info('Accountify seeded: Company, User, and tenant-scoped records created!');
    }
}
