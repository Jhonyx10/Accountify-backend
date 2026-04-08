<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = User::where('type', 'company')->first();
        if (!$company) {
            return;
        }

        $creatorId = $company->id;

        // Get some accounts
        $salesAcc = ChartOfAccount::where('name', 'Sales')->first();
        $servicesAcc = ChartOfAccount::where('name', 'Services')->first();
        $rentAcc = ChartOfAccount::where('name', 'Rent Expense')->first();
        $salariesAcc = ChartOfAccount::where('name', 'Salaries & Wages')->first();
        $advertisingAcc = ChartOfAccount::where('name', 'Advertising')->first();

        // 1. Yearly Operating Budget
        Budget::create([
            'name' => 'FY 2024 Operating Budget',
            'period' => 'yearly',
            'from' => '2024',
            'income_data' => [
                $salesAcc?->id ?? 1 => [500000],
                $servicesAcc?->id ?? 2 => [100000],
            ],
            'expense_data' => [
                $rentAcc?->id ?? 4 => [120000],
                $salariesAcc?->id ?? 6 => [300000],
                $advertisingAcc?->id ?? 7 => [50000],
            ],
            'created_by' => $creatorId,
        ]);

        // 2. Quarterly Marketing Budget
        Budget::create([
            'name' => 'Q1-Q4 Marketing Push',
            'period' => 'quarterly',
            'from' => '2024-01',
            'to' => '2024-12',
            'income_data' => [
                $salesAcc?->id ?? 1 => [100000, 120000, 150000, 200000],
            ],
            'expense_data' => [
                $advertisingAcc?->id ?? 7 => [10000, 15000, 20000, 30000],
            ],
            'created_by' => $creatorId,
        ]);

        // 3. Monthly Office Expenses
        Budget::create([
            'name' => 'Monthly Recurring Expenses',
            'period' => 'monthly',
            'from' => '2024-01',
            'income_data' => [],
            'expense_data' => [
                $rentAcc?->id ?? 4 => array_fill(0, 12, 10000),
                $salariesAcc?->id ?? 6 => array_fill(0, 12, 25000),
            ],
            'created_by' => $creatorId,
        ]);
    }
}
