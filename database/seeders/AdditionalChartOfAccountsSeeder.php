<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdditionalChartOfAccountsSeeder extends Seeder
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

        $incomeType = ChartOfAccountType::where('name', 'Income')->first();
        $expenseType = ChartOfAccountType::where('name', 'Expenses')->first();
        
        $incomeSubType = ChartOfAccountSubType::where('name', 'Income')->first();
        $expenseSubType = ChartOfAccountSubType::where('name', 'Expense')->first();

        $accounts = [
            // Income
            ['name' => 'Interest Income', 'code' => '4200', 'type' => $incomeType->id, 'sub_type' => $incomeSubType->id],
            ['name' => 'Rental Income', 'code' => '4300', 'type' => $incomeType->id, 'sub_type' => $incomeSubType->id],
            ['name' => 'Consulting Fees', 'code' => '4400', 'type' => $incomeType->id, 'sub_type' => $incomeSubType->id],

            // Expenses
            ['name' => 'Travel Expense', 'code' => '6300', 'type' => $expenseType->id, 'sub_type' => $expenseSubType->id],
            ['name' => 'Software Subscriptions', 'code' => '6400', 'type' => $expenseType->id, 'sub_type' => $expenseSubType->id],
            ['name' => 'Office Supplies', 'code' => '6500', 'type' => $expenseType->id, 'sub_type' => $expenseSubType->id],
            ['name' => 'Insurance', 'code' => '6600', 'type' => $expenseType->id, 'sub_type' => $expenseSubType->id],
            ['name' => 'Legal Fees', 'code' => '6700', 'type' => $expenseType->id, 'sub_type' => $expenseSubType->id],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate([
                'code' => $acc['code'],
                'created_by' => $creatorId
            ], [
                'name' => $acc['name'],
                'type' => $acc['type'],
                'sub_type' => $acc['sub_type'],
                'is_enabled' => 1,
                'description' => 'Additional ' . $acc['name'] . ' account',
            ]);
        }
    }
}
