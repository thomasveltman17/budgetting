<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Period;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->travelTo('2026-08-20');

        $closed = Period::create(['start_date' => '2026-07-15', 'end_date' => '2026-08-14', 'is_current' => false]);
        Period::create(['start_date' => '2026-08-15', 'end_date' => '2026-09-14', 'is_current' => true]);

        $amex = Account::where('name', 'amex')->firstOrFail();
        $revolut = Account::where('name', 'revolut')->firstOrFail();

        $dinner = Transaction::create([
            'period_id' => $closed->id,
            'account_id' => $amex->id,
            'category_id' => Category::where('name', 'Short-term Spends')->value('id'),
            'date' => '2026-07-20',
            'description' => 'Dinner with friends',
            'amount' => -90,
            'source' => 'manual',
        ]);
        Transaction::create([
            'period_id' => $closed->id,
            'account_id' => $revolut->id,
            'parent_transaction_id' => $dinner->id,
            'date' => '2026-07-21',
            'description' => 'Tikkie dinner',
            'amount' => 30,
            'source' => 'manual',
        ]);
        Transaction::create([
            'period_id' => $closed->id,
            'account_id' => $revolut->id,
            'date' => '2026-07-22',
            'description' => 'Returned jacket',
            'amount' => -60,
            'source' => 'manual',
            'is_pending_return' => true,
        ]);

        session(['authenticated' => true, 'selected_period_id' => $closed->id]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['dashboard', 'Spent this period'],
            'transactions' => ['transactions', 'Tikkie dinner'],
            'history' => ['history', 'Spent per category'],
            'settings' => ['settings', 'Budget targets'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders(string $routeName, string $expectedText): void
    {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee($expectedText);
    }

    public function test_uncategorized_spending_is_flagged_on_the_dashboard(): void
    {
        Transaction::query()->update(['is_pending_return' => false]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 expense has no category yet');
    }

    public function test_provider_logos_are_shown_for_accounts(): void
    {
        $this->get(route('transactions'))
            ->assertOk()
            ->assertSee('images/providers/amex.png')
            ->assertSee('images/providers/revolut.png');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('images/providers/rabobank.png');
    }

    public function test_login_page_renders_for_guests(): void
    {
        session()->forget('authenticated');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in');
    }
}
