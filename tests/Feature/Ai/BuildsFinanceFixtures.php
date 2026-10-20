<?php

namespace Tests\Feature\Ai;

use App\Models\Account;
use App\Models\Category;
use App\Models\Period;
use App\Models\Transaction;

/**
 * Two budget periods with known totals. On 2026-08-20 the current period
 * has spending of 930.50: rent 800, dinner 90 less a 30 repayment, groceries
 * 45.50 and an uncategorized 25. The 500 investment transfer and the pending
 * Zalando return are not spending.
 */
trait BuildsFinanceFixtures
{
    protected Period $previousPeriod;

    protected Period $currentPeriod;

    /** @var array<string, Transaction> */
    protected array $fixtures = [];

    protected function buildFinanceFixtures(): void
    {
        $this->seed();
        $this->travelTo('2026-08-20');

        $this->previousPeriod = Period::create(['start_date' => '2026-07-15', 'end_date' => '2026-08-14', 'is_current' => false]);
        $this->currentPeriod = Period::create(['start_date' => '2026-08-15', 'end_date' => '2026-09-14', 'is_current' => true]);

        $this->addTransaction('previousSalary', $this->previousPeriod, 'rabobank', null, '2026-07-15', 'Salary', 2500);
        $this->addTransaction('previousRent', $this->previousPeriod, 'rabobank', 'Fixed Costs', '2026-07-16', 'Rent', -800);
        $this->addTransaction('previousGroceries', $this->previousPeriod, 'amex', 'Short-term Spends', '2026-07-20', 'ALBERT HEIJN 1234', -100);

        $this->addTransaction('salary', $this->currentPeriod, 'rabobank', null, '2026-08-15', 'Salary', 2600);
        $this->addTransaction('rent', $this->currentPeriod, 'rabobank', 'Fixed Costs', '2026-08-16', 'Rent', -800);
        $this->addTransaction('dinner', $this->currentPeriod, 'amex', 'Short-term Spends', '2026-08-17', 'Dinner with friends', -90);
        $this->addTransaction('repayment', $this->currentPeriod, 'revolut', null, '2026-08-18', 'Tikkie dinner', 30, ['parent_transaction_id' => $this->fixtures['dinner']->id]);
        $this->addTransaction('groceries', $this->currentPeriod, 'amex', 'Short-term Spends', '2026-08-18', 'ALBERT HEIJN 1234', -45.50, ['notes' => 'Weekly shop']);
        $this->addTransaction('investment', $this->currentPeriod, 'rabobank', 'Investments', '2026-08-18', 'Trading 212 deposit', -500);
        $this->addTransaction('uncategorized', $this->currentPeriod, 'revolut', null, '2026-08-19', 'Bol.com', -25);
        $this->addTransaction('pendingReturn', $this->currentPeriod, 'amex', 'Short-term Spends', '2026-08-19', 'Zalando', -120, ['is_pending_return' => true]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function addTransaction(string $key, Period $period, string $account, ?string $category, string $date, string $description, float $amount, array $attributes = []): void
    {
        $this->fixtures[$key] = Transaction::create([
            'period_id' => $period->id,
            'account_id' => Account::where('name', $account)->value('id'),
            'category_id' => $category ? Category::where('name', $category)->value('id') : null,
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'source' => 'manual',
            ...$attributes,
        ]);
    }
}
