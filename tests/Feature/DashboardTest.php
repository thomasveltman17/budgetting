<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Account;
use App\Models\Period;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Period $period;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->travelTo('2026-07-20');

        $this->period = Period::create([
            'start_date' => '2026-07-15',
            'end_date' => '2026-08-14',
            'is_current' => true,
        ]);
        $this->account = Account::where('name', 'revolut')->firstOrFail();

        session(['selected_period_id' => $this->period->id]);
    }

    public function test_period_length_includes_both_the_first_and_last_day(): void
    {
        $this->assertSame(31, $this->period->lengthInDays());
    }

    public function test_elapsed_days_counts_today_and_stays_within_the_period(): void
    {
        $this->assertSame(0, $this->period->elapsedDays(Carbon::parse('2026-07-14')));
        $this->assertSame(1, $this->period->elapsedDays(Carbon::parse('2026-07-15 23:59')));
        $this->assertSame(6, $this->period->elapsedDays(Carbon::parse('2026-07-20')));
        $this->assertSame(31, $this->period->elapsedDays(Carbon::parse('2026-08-14')));
        $this->assertSame(31, $this->period->elapsedDays(Carbon::parse('2026-09-01')));
    }

    public function test_daily_activity_has_one_entry_per_day_of_the_period(): void
    {
        $days = Livewire::test(Dashboard::class)->instance()->dailyActivity;

        $this->assertCount(31, $days);
        $this->assertSame('2026-07-15', $days->first()['date']->toDateString());
        $this->assertSame('2026-08-14', $days->last()['date']->toDateString());
    }

    public function test_daily_activity_nets_repayments_and_skips_pending_returns(): void
    {
        $dinner = $this->addTransaction('2026-07-16', -80);
        $this->addTransaction('2026-07-18', 30, ['parent_transaction_id' => $dinner->id]);
        $this->addTransaction('2026-07-16', -20);
        $this->addTransaction('2026-07-16', -50, ['is_pending_return' => true]);
        $this->addTransaction('2026-07-17', 2500);

        $days = Livewire::test(Dashboard::class)->instance()->dailyActivity
            ->keyBy(fn (array $day) => $day['date']->toDateString());

        $this->assertEqualsWithDelta(70.0, $days['2026-07-16']['spent'], 0.001);
        $this->assertSame(0.0, $days['2026-07-16']['received']);
        $this->assertSame(0.0, $days['2026-07-17']['spent']);
        $this->assertEqualsWithDelta(2500.0, $days['2026-07-17']['received'], 0.001);
        $this->assertSame(0.0, $days['2026-07-18']['received'], 'Linked repayments are not counted as money in.');
    }

    public function test_an_expense_fully_paid_back_does_not_count_as_negative_spending(): void
    {
        $tickets = $this->addTransaction('2026-07-16', -40);
        $this->addTransaction('2026-07-16', 60, ['parent_transaction_id' => $tickets->id]);

        $days = Livewire::test(Dashboard::class)->instance()->dailyActivity
            ->keyBy(fn (array $day) => $day['date']->toDateString());

        $this->assertSame(0.0, $days['2026-07-16']['spent']);
    }

    public function test_dashboard_renders_the_period_ruler_and_totals(): void
    {
        $this->addTransaction('2026-07-16', -1234.5);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Day 6 of 31')
            ->assertSee('1.234,50')
            ->assertSee('Highest day Thu 16 Jul');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function addTransaction(string $date, float $amount, array $attributes = []): Transaction
    {
        return Transaction::create([
            'period_id' => $this->period->id,
            'account_id' => $this->account->id,
            'date' => $date,
            'description' => 'Test transaction',
            'amount' => $amount,
            'source' => 'manual',
            ...$attributes,
        ]);
    }
}
