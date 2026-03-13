<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Plan;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Free Plan',
            'price' => 0,
            'duration' => 'lifetime',
            'max_users' => 5,
            'max_customers' => 5,
            'max_venders' => 5,
            'image' => 'free_plan.png',
        ]);
    }
}
