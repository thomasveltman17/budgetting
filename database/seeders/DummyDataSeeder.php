<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BudgetTarget;
use App\Models\Category;
use App\Models\NetWorthAccount;
use App\Models\NetWorthSnapshot;
use App\Models\Period;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNetWorthAccounts();
        $this->seedPeriods();
    }

    private function seedNetWorthAccounts(): void
    {
        $accounts = [
            ['name' => 'Spaarrekening', 'type' => 'savings', 'notes' => 'ABN AMRO spaarrekening', 'balance' => 14500.00, 'sort_order' => 1],
            ['name' => 'Noodfonds', 'type' => 'savings', 'notes' => '3 maanden levensonderhoud', 'balance' => 5000.00, 'sort_order' => 2],
            ['name' => 'DeGiro Portfolio', 'type' => 'investment', 'notes' => 'World ETF portefeuille', 'balance' => 28750.00, 'sort_order' => 3],
        ];

        foreach ($accounts as $data) {
            $account = NetWorthAccount::firstOrCreate(
                ['name' => $data['name']],
                [
                    'type' => $data['type'],
                    'notes' => $data['notes'],
                    'is_active' => true,
                    'sort_order' => $data['sort_order'],
                ]
            );

            if ($account->snapshots()->doesntExist()) {
                NetWorthSnapshot::create([
                    'net_worth_account_id' => $account->id,
                    'balance' => $data['balance'],
                    'recorded_at' => Carbon::now()->subDays(2),
                ]);
            }
        }
    }

    private function seedPeriods(): void
    {
        $rabobank = Account::where('name', 'rabobank')->firstOrFail();
        $revolut = Account::where('name', 'revolut')->firstOrFail();
        $amex = Account::where('name', 'amex')->firstOrFail();

        $fixed = Category::where('name', 'Fixed Costs')->firstOrFail();
        $longTerm = Category::where('name', 'Long-term Spends')->firstOrFail();
        $shortTerm = Category::where('name', 'Short-term Spends')->firstOrFail();

        // ── Current period: 15 Apr – 14 May 2026 ──────────────────────────
        $current = Period::firstOrCreate(
            ['start_date' => '2026-04-15'],
            ['end_date' => '2026-05-14', 'is_current' => true]
        );

        if ($current->transactions()->doesntExist()) {
            $this->seedBudgetTargets($current->id, $fixed->id, $longTerm->id, $shortTerm->id);
            $this->seedCurrentPeriodTransactions($current, $rabobank, $revolut, $amex, $fixed, $longTerm, $shortTerm);
        }

        // ── Past period 1: 15 Mar – 14 Apr 2026 ──────────────────────────
        $past1 = Period::firstOrCreate(
            ['start_date' => '2026-03-15'],
            ['end_date' => '2026-04-14', 'is_current' => false, 'amex_paid_at' => '2026-04-16 10:15:00']
        );

        if ($past1->transactions()->doesntExist()) {
            $this->seedBudgetTargets($past1->id, $fixed->id, $longTerm->id, $shortTerm->id);
            $this->seedPast1Transactions($past1, $rabobank, $revolut, $amex, $fixed, $longTerm, $shortTerm);
        }

        // ── Past period 2: 15 Feb – 14 Mar 2026 ──────────────────────────
        $past2 = Period::firstOrCreate(
            ['start_date' => '2026-02-15'],
            ['end_date' => '2026-03-14', 'is_current' => false, 'amex_paid_at' => '2026-03-16 09:45:00']
        );

        if ($past2->transactions()->doesntExist()) {
            $this->seedBudgetTargets($past2->id, $fixed->id, $longTerm->id, $shortTerm->id);
            $this->seedPast2Transactions($past2, $rabobank, $revolut, $amex, $fixed, $longTerm, $shortTerm);
        }
    }

    private function seedBudgetTargets(int $periodId, mixed $fixedId, mixed $longTermId, mixed $shortTermId): void
    {
        $targets = [
            [$fixedId, 1600.00],
            [$longTermId, 500.00],
            [$shortTermId, 700.00],
        ];

        foreach ($targets as [$categoryId, $amount]) {
            BudgetTarget::firstOrCreate(
                ['period_id' => $periodId, 'category_id' => $categoryId],
                ['amount' => $amount]
            );
        }
    }

    private function seedCurrentPeriodTransactions(
        Period $period,
        Account $rabobank,
        Account $revolut,
        Account $amex,
        Category $fixed,
        Category $longTerm,
        Category $shortTerm
    ): void {
        $p = $period->id;

        $transactions = [
            // Rabobank
            [$rabobank->id, '2026-04-15', 'Huur woning april', -1150.00, $fixed->id],
            [$rabobank->id, '2026-04-15', 'Salaris april', 3250.00, null],
            [$rabobank->id, '2026-04-16', 'KPN Internet', -49.99, $fixed->id],
            [$rabobank->id, '2026-04-16', 'Zorgverzekering CZ', -142.50, $fixed->id],
            [$rabobank->id, '2026-04-22', 'Spotify Premium', -11.99, $fixed->id],
            [$rabobank->id, '2026-04-28', 'Gym abonnement', -29.95, $shortTerm->id],

            // Revolut — mix of categorized + 2 uncategorized to trigger dashboard banner
            [$revolut->id, '2026-04-17', 'Albert Heijn', -42.50, $shortTerm->id],
            [$revolut->id, '2026-04-20', 'Thuisbezorgd', -34.80, null],        // uncategorized
            [$revolut->id, '2026-04-25', 'Jumbo Supermarkt', -38.90, $shortTerm->id],
            [$revolut->id, '2026-04-28', 'Shell Tankstation', -68.00, null],   // uncategorized
            [$revolut->id, '2026-05-02', 'Croissanterie', -22.50, $shortTerm->id],
            [$revolut->id, '2026-05-04', 'H&M Online', -64.95, $longTerm->id],

            // AmEx
            [$amex->id, '2026-04-18', 'Zalando bestelling', -89.95, $longTerm->id],
            [$amex->id, '2026-04-25', 'Booking.com hotel', -180.00, $longTerm->id],
            [$amex->id, '2026-04-30', 'Apple App Store', -4.99, $shortTerm->id],
            [$amex->id, '2026-05-03', 'Restaurant De Kas', -67.50, $shortTerm->id],
        ];

        foreach ($transactions as [$accountId, $date, $description, $amount, $categoryId]) {
            Transaction::create([
                'period_id' => $p,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'date' => $date,
                'description' => $description,
                'amount' => $amount,
                'source' => 'manual',
            ]);
        }
    }

    private function seedPast1Transactions(
        Period $period,
        Account $rabobank,
        Account $revolut,
        Account $amex,
        Category $fixed,
        Category $longTerm,
        Category $shortTerm
    ): void {
        $p = $period->id;

        $transactions = [
            // Rabobank
            [$rabobank->id, '2026-03-15', 'Huur woning maart', -1150.00, $fixed->id],
            [$rabobank->id, '2026-03-15', 'Salaris maart', 3250.00, null],
            [$rabobank->id, '2026-03-16', 'KPN Internet', -49.99, $fixed->id],
            [$rabobank->id, '2026-03-16', 'Zorgverzekering CZ', -142.50, $fixed->id],
            [$rabobank->id, '2026-03-17', 'Spotify Premium', -11.99, $fixed->id],
            [$rabobank->id, '2026-03-20', 'Jumbo boodschappen', -67.80, $shortTerm->id],
            [$rabobank->id, '2026-03-25', 'Gym abonnement', -29.95, $shortTerm->id],
            [$rabobank->id, '2026-04-02', 'Thuisbezorgd', -28.50, $shortTerm->id],
            [$rabobank->id, '2026-04-05', 'IKEA boekenplank', -89.00, $longTerm->id],

            // Revolut
            [$revolut->id, '2026-03-16', 'Albert Heijn', -34.50, $shortTerm->id],
            [$revolut->id, '2026-03-18', 'Broodjeszaak', -18.90, $shortTerm->id],
            [$revolut->id, '2026-03-20', 'H&M jas', -79.95, $longTerm->id],
            [$revolut->id, '2026-03-25', 'Shell tankstation', -65.00, $fixed->id],
            [$revolut->id, '2026-03-28', 'Bol.com boeken', -39.99, $longTerm->id],
            [$revolut->id, '2026-04-02', 'Spar boodschappen', -22.30, $shortTerm->id],
            [$revolut->id, '2026-04-08', 'Coolblue toetsenbord', -89.00, $longTerm->id],
            [$revolut->id, '2026-04-10', 'Netflix', -7.99, $fixed->id],

            // AmEx (all paid on Apr 16)
            [$amex->id, '2026-03-17', 'Booking.com vlucht', -289.00, $longTerm->id],
            [$amex->id, '2026-03-19', 'Apple App Store', -4.99, $shortTerm->id],
            [$amex->id, '2026-03-23', 'Zalando schoenen', -124.95, $longTerm->id],
            [$amex->id, '2026-03-30', 'Restaurant Bij Ons', -85.00, $shortTerm->id],
            [$amex->id, '2026-04-05', 'Decathlon', -52.95, $shortTerm->id],
            [$amex->id, '2026-04-12', 'Airbnb Barcelona', -320.00, $longTerm->id],
        ];

        foreach ($transactions as [$accountId, $date, $description, $amount, $categoryId]) {
            Transaction::create([
                'period_id' => $p,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'date' => $date,
                'description' => $description,
                'amount' => $amount,
                'source' => 'manual',
            ]);
        }
    }

    private function seedPast2Transactions(
        Period $period,
        Account $rabobank,
        Account $revolut,
        Account $amex,
        Category $fixed,
        Category $longTerm,
        Category $shortTerm
    ): void {
        $p = $period->id;

        $transactions = [
            // Rabobank
            [$rabobank->id, '2026-02-15', 'Huur woning februari', -1150.00, $fixed->id],
            [$rabobank->id, '2026-02-15', 'Salaris februari', 3250.00, null],
            [$rabobank->id, '2026-02-16', 'KPN Internet', -49.99, $fixed->id],
            [$rabobank->id, '2026-02-16', 'Zorgverzekering CZ', -142.50, $fixed->id],
            [$rabobank->id, '2026-02-17', 'Spotify Premium', -11.99, $fixed->id],
            [$rabobank->id, '2026-02-22', 'Jumbo boodschappen', -55.40, $shortTerm->id],
            [$rabobank->id, '2026-02-28', 'Gym abonnement', -29.95, $shortTerm->id],

            // Revolut
            [$revolut->id, '2026-02-16', 'Albert Heijn', -41.20, $shortTerm->id],
            [$revolut->id, '2026-02-20', 'Sushi restaurant', -48.50, $shortTerm->id],
            [$revolut->id, '2026-02-24', 'Zara online', -59.95, $longTerm->id],
            [$revolut->id, '2026-03-01', 'Kruidvat', -18.75, $shortTerm->id],
            [$revolut->id, '2026-03-05', 'Steam games', -29.99, $shortTerm->id],
            [$revolut->id, '2026-03-09', 'Elektrische tandenborstel', -79.00, $longTerm->id],
            [$revolut->id, '2026-03-12', 'Netflix', -7.99, $fixed->id],

            // AmEx (all paid on Mar 16)
            [$amex->id, '2026-02-18', 'Weekendje Gent hotel', -195.00, $longTerm->id],
            [$amex->id, '2026-02-22', 'Apple One', -19.99, $fixed->id],
            [$amex->id, '2026-02-27', 'Zalando vest', -89.95, $longTerm->id],
            [$amex->id, '2026-03-03', 'Dinner Rijks', -95.00, $shortTerm->id],
            [$amex->id, '2026-03-08', 'Douglas parfum', -74.50, $shortTerm->id],
            [$amex->id, '2026-03-13', 'Booking.com weekend', -220.00, $longTerm->id],
        ];

        foreach ($transactions as [$accountId, $date, $description, $amount, $categoryId]) {
            Transaction::create([
                'period_id' => $p,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'date' => $date,
                'description' => $description,
                'amount' => $amount,
                'source' => 'manual',
            ]);
        }
    }
}
