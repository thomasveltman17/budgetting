<?php

namespace Tests\Feature\Ai;

use App\Models\BudgetTarget;
use App\Models\Category;
use App\Services\Ai\FinanceTools;
use App\Services\Ai\InsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceDataTest extends TestCase
{
    use BuildsFinanceFixtures;
    use RefreshDatabase;

    private FinanceTools $tools;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildFinanceFixtures();
        $this->tools = app(FinanceTools::class);
    }

    public function test_overview_nets_repayments_and_leaves_out_pending_returns_and_investments(): void
    {
        $overview = $this->tools->call('get_period_overview', []);

        $this->assertSame($this->currentPeriod->id, $overview['period']['id']);
        $this->assertSame(2600.0, $overview['income']);
        $this->assertSame(930.5, $overview['spending']);
        $this->assertSame(500.0, $overview['saved_and_invested']);
        $this->assertSame(1430.5, $overview['all_outflows']);
        $this->assertSame(1169.5, $overview['net']);
        $this->assertSame(1, $overview['uncategorized_count']);
        $this->assertSame(25.0, $overview['uncategorized_amount']);
        $this->assertSame(6, $overview['days_elapsed']);
        $this->assertSame(31, $overview['days_total']);
        $this->assertSame(900.0, $overview['previous_period']['spending']);
        $this->assertSame(3.4, $overview['previous_period']['spending_change_pct']);
    }

    public function test_previous_period_can_be_requested(): void
    {
        $overview = $this->tools->call('get_period_overview', ['period' => 'previous']);

        $this->assertSame($this->previousPeriod->id, $overview['period']['id']);
        $this->assertSame(900.0, $overview['spending']);
    }

    public function test_spending_by_category_is_largest_first_and_includes_uncategorized(): void
    {
        $result = $this->tools->call('spending_by_category', []);

        $this->assertSame(930.5, $result['total']);
        $this->assertSame(['Fixed Costs', 'Short-term Spends', 'Uncategorized'], array_column($result['categories'], 'category'));
        $this->assertSame(105.5, $result['categories'][1]['spent']);
        $this->assertSame(2, $result['categories'][1]['transaction_count']);
    }

    public function test_scope_all_includes_investments(): void
    {
        $result = $this->tools->call('spending_by_category', ['scope' => 'all']);

        $this->assertSame(1430.5, $result['total']);
        $this->assertContains('Investments', array_column($result['categories'], 'category'));
    }

    public function test_top_transactions_are_ranked_by_net_amount(): void
    {
        $result = $this->tools->call('top_transactions', ['limit' => 3]);

        $this->assertSame(['Rent', 'Dinner with friends', 'ALBERT HEIJN 1234'], array_column($result['transactions'], 'description'));
        $this->assertSame(-60.0, $result['transactions'][1]['net_amount_after_repayments']);
    }

    public function test_top_transactions_can_be_filtered_by_category_and_account(): void
    {
        $result = $this->tools->call('top_transactions', ['category' => 'short-term spends', 'account' => 'American Express']);

        $this->assertSame([$this->fixtures['dinner']->id, $this->fixtures['groceries']->id], array_column($result['transactions'], 'id'));
    }

    public function test_search_finds_text_in_descriptions_and_notes_across_periods(): void
    {
        $byDescription = $this->tools->call('search_transactions', ['query' => 'albert heijn']);
        $byNote = $this->tools->call('search_transactions', ['query' => 'weekly']);

        $this->assertSame(2, $byDescription['total_matched']);
        $this->assertSame(-145.5, $byDescription['total_amount']);
        $this->assertSame([$this->fixtures['groceries']->id], array_column($byNote['transactions'], 'id'));
        $this->assertSame('Weekly shop', $byNote['transactions'][0]['notes']);
    }

    public function test_search_filters_by_amount_date_and_direction(): void
    {
        $result = $this->tools->call('search_transactions', [
            'min_amount' => 40,
            'max_amount' => 150,
            'date_from' => '2026-08-15',
            'direction' => 'expense',
            'sort' => 'amount',
        ]);

        $this->assertSame([$this->fixtures['pendingReturn']->id, $this->fixtures['dinner']->id, $this->fixtures['groceries']->id], array_column($result['transactions'], 'id'));
        $this->assertTrue($result['transactions'][0]['pending_return']);
    }

    public function test_budget_status_reports_progress_against_targets(): void
    {
        BudgetTarget::create([
            'category_id' => Category::where('name', 'Short-term Spends')->value('id'),
            'period_id' => $this->currentPeriod->id,
            'amount' => 120,
        ]);

        $result = $this->tools->call('budget_status', []);

        $this->assertSame('near_limit', $result['targets'][0]['status']);
        $this->assertSame(87.9, $result['targets'][0]['pct_used']);
        $this->assertSame(14.5, $result['targets'][0]['remaining']);
    }

    public function test_spending_trend_lists_periods_oldest_first(): void
    {
        $result = $this->tools->call('spending_trend', ['periods' => 6]);

        $this->assertSame([900.0, 930.5], array_column($result['periods'], 'spending'));
        $this->assertSame([2500.0, 2600.0], array_column($result['periods'], 'income'));
    }

    public function test_top_merchants_groups_by_merchant_name(): void
    {
        $result = $this->tools->call('top_merchants', ['period' => (string) $this->currentPeriod->id]);

        $this->assertSame('Rent', $result['merchants'][0]['merchant']);
        $this->assertSame(800.0, $result['merchants'][0]['spent']);
    }

    public function test_bad_arguments_come_back_as_errors_for_the_model(): void
    {
        $this->assertStringContainsString('Unknown category "Holidays"', $this->tools->call('top_transactions', ['category' => 'Holidays'])['error']);
        $this->assertStringContainsString('Valid accounts', $this->tools->call('search_transactions', ['account' => 'ING'])['error']);
        $this->assertStringContainsString('No period with id 999', $this->tools->call('get_period_overview', ['period' => '999'])['error']);
        $this->assertStringContainsString('YYYY-MM-DD', $this->tools->call('search_transactions', ['date_from' => 'last week'])['error']);
        $this->assertSame('Unknown tool "delete_everything".', $this->tools->call('delete_everything', [])['error']);
    }

    public function test_every_tool_definition_is_callable(): void
    {
        foreach ($this->tools->definitions() as $definition) {
            $this->assertArrayNotHasKey('error', $this->tools->call($definition['name'], []), $definition['name']);
        }
    }

    public function test_insights_point_out_uncategorized_spending_and_the_biggest_expense(): void
    {
        $insights = collect(app(InsightService::class)->forCurrentPeriod());

        $this->assertNotNull($insights->firstWhere('title', '1 expense without a category'));
        $this->assertNotNull($insights->firstWhere('title', 'Biggest expense: Rent'));
        $insights->each(fn (array $insight) => $this->assertNotSame('', $insight['prompt']));
    }
}
