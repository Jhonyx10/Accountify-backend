<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Assets' => [
                'Current Asset',
                'Fixed Asset',
                'Inventory',
                'Non-current Asset',
                'Prepayment',
                'Bank & Cash',
                'Depreciation',
            ],
            'Liabilities' => [
                'Current Liability',
                'Liability',
                'Non-current Liability',
            ],
            'Equity' => [
                'Equity',
            ],
            'Income' => [
                'Income',
                'Other Income',
            ],
            'Costs of Goods Sold' => [
                'Costs of Goods Sold',
            ],
            'Expenses' => [
                'Expense',
                'Other Expense',
            ],
        ];

        foreach ($types as $type => $subTypes) {
            $createdType = ChartOfAccountType::firstOrCreate([
                'name' => $type,
                'created_by' => 0
            ]);

            foreach ($subTypes as $subType) {
                ChartOfAccountSubType::firstOrCreate([
                    'name' => $subType,
                    'type' => $createdType->id,
                    'created_by' => 0,
                ]);
            }
        }

        // Seed some actual default accounts for the default company
        $defaultAccounts = [
            ['name' => 'Cash', 'code' => '1000', 'type_name' => 'Assets', 'sub_type_name' => 'Bank & Cash'],
            ['name' => 'Checking Account', 'code' => '1010', 'type_name' => 'Assets', 'sub_type_name' => 'Bank & Cash'],
            ['name' => 'Accounts Receivable', 'code' => '1200', 'type_name' => 'Assets', 'sub_type_name' => 'Current Asset'],
            ['name' => 'Inventory Asset', 'code' => '1400', 'type_name' => 'Assets', 'sub_type_name' => 'Inventory'],
            ['name' => 'Accounts Payable', 'code' => '2000', 'type_name' => 'Liabilities', 'sub_type_name' => 'Current Liability'],
            ['name' => 'Sales Tax Payable', 'code' => '2200', 'type_name' => 'Liabilities', 'sub_type_name' => 'Current Liability'],
            ['name' => 'Owner\'s Equity', 'code' => '3000', 'type_name' => 'Equity', 'sub_type_name' => 'Equity'],
            ['name' => 'Retained Earnings', 'code' => '3200', 'type_name' => 'Equity', 'sub_type_name' => 'Equity'],
            ['name' => 'Sales', 'code' => '4000', 'type_name' => 'Income', 'sub_type_name' => 'Income'],
            ['name' => 'Services', 'code' => '4100', 'type_name' => 'Income', 'sub_type_name' => 'Income'],
            ['name' => 'Cost of Goods Sold', 'code' => '5000', 'type_name' => 'Costs of Goods Sold', 'sub_type_name' => 'Costs of Goods Sold'],
            ['name' => 'Advertising', 'code' => '6000', 'type_name' => 'Expenses', 'sub_type_name' => 'Expense'],
            ['name' => 'Bank Charges', 'code' => '6010', 'type_name' => 'Expenses', 'sub_type_name' => 'Expense'],
            ['name' => 'Rent Expense', 'code' => '6100', 'type_name' => 'Expenses', 'sub_type_name' => 'Expense'],
            ['name' => 'Salaries & Wages', 'code' => '6200', 'type_name' => 'Expenses', 'sub_type_name' => 'Expense'],
        ];

        // We assume User ID 2 is the default 'company' created from UsersTableSeeder
        $defaultCompanyId = \App\Models\User::where('type', 'company')->value('id') ?? 2;

        foreach ($defaultAccounts as $acc) {
            $type = ChartOfAccountType::where('name', $acc['type_name'])->first();
            $subType = ChartOfAccountSubType::where('name', $acc['sub_type_name'])->first();

            if ($type && $subType) {
                \App\Models\ChartOfAccount::firstOrCreate([
                    'code' => $acc['code'],
                    'created_by' => $defaultCompanyId
                ], [
                    'name' => $acc['name'],
                    'type' => $type->id,
                    'sub_type' => $subType->id,
                    'is_enabled' => 1,
                    'description' => 'Default ' . $acc['name'] . ' account',
                ]);
            }
        }
    }
}
