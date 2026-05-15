<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Income Categories
            ['name' => 'Product Sales', 'type' => 'Income', 'description' => 'Revenue from physical goods', 'color' => '#28a745'],
            ['name' => 'Consulting', 'type' => 'Income', 'description' => 'Revenue from services', 'color' => '#28a745'],
            ['name' => 'Sales Revenue', 'type' => 'Income', 'description' => 'Direct sales revenue', 'color' => '#28a745'],
            ['name' => 'Service Revenue', 'type' => 'Income', 'description' => 'Revenue from service delivery', 'color' => '#28a745'],
            ['name' => 'Interest Income', 'type' => 'Income', 'description' => 'Income from bank interest', 'color' => '#28a745'],
            
            // Expense Categories
            ['name' => 'Rent', 'type' => 'Expense', 'description' => 'Monthly office rent', 'color' => '#dc3545'],
            ['name' => 'Office Supplies', 'type' => 'Expense', 'description' => 'Stationery and items', 'color' => '#dc3545'],
            ['name' => 'Rent Expense', 'type' => 'Expense', 'description' => 'Rent related costs', 'color' => '#dc3545'],
            ['name' => 'Utilities', 'type' => 'Expense', 'description' => 'Electricity, water, etc.', 'color' => '#dc3545'],
            ['name' => 'Payroll', 'type' => 'Expense', 'description' => 'Employee salaries', 'color' => '#dc3545'],
            ['name' => 'Marketing', 'type' => 'Expense', 'description' => 'Advertising and outreach', 'color' => '#dc3545'],
            
            // Product Categories
            ['name' => 'Electronics', 'type' => 'Product', 'description' => 'Electronic devices', 'color' => '#17a2b8'],
            ['name' => 'Hardware', 'type' => 'Product', 'description' => 'Physical hardware components', 'color' => '#17a2b8'],
            ['name' => 'Software', 'type' => 'Product', 'description' => 'Digital software products', 'color' => '#17a2b8'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                ['description' => $category['description'], 'color' => $category['color'], 'created_by' => 1]
            );
        }
    }
}
