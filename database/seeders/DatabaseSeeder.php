<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedAccounts();
        $this->seedCategories();
    }

    private function seedAccounts(): void
    {
        $accounts = [
            ['name' => 'rabobank', 'label' => 'Rabobank', 'color' => '#2563eb'],
            ['name' => 'revolut', 'label' => 'Revolut', 'color' => '#7c3aed'],
            ['name' => 'amex', 'label' => 'American Express', 'color' => '#059669'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(['name' => $account['name']], $account);
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Fixed Costs', 'type' => 'transactional', 'color' => '#ef4444', 'sort_order' => 1],
            ['name' => 'Long-term Spends', 'type' => 'transactional', 'color' => '#f59e0b', 'sort_order' => 2],
            ['name' => 'Short-term Spends', 'type' => 'transactional', 'color' => '#3b82f6', 'sort_order' => 3],
            ['name' => 'Savings', 'type' => 'savings', 'color' => '#10b981', 'sort_order' => 4],
            ['name' => 'Investments', 'type' => 'investment', 'color' => '#8b5cf6', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
