<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
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

        $types = [
            'Maintenance',
            'Development',
            'Consulting',
            'SaaS Subscription',
            'Project Based',
        ];

        foreach ($types as $type) {
            ContractType::firstOrCreate([
                'name' => $type,
                'created_by' => $company->id,
            ]);
        }
    }
}
