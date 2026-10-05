<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Category;
use App\Models\Period;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortcutTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-shortcuts-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        config(['services.shortcuts.token' => self::TOKEN]);
    }

    public function test_it_logs_an_expense_from_a_currency_formatted_amount(): void
    {
        $response = $this->withToken(self::TOKEN)->postJson(route('api.transactions.store'), [
            'amount' => '€ 1.234,56',
            'description' => 'Albert Heijn',
            'account' => 'Revolut',
            'category' => 'short-term spends',
            'date' => '2026-08-14T18:30:00+02:00',
            'notes' => 'Weekly groceries',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', -1234.56)
            ->assertJsonPath('data.date', '2026-08-14')
            ->assertJsonPath('data.account', 'Revolut')
            ->assertJsonPath('data.category', 'Short-term Spends')
            ->assertJsonPath('data.period', '15 Jul – 14 Aug')
            ->assertJsonPath('message', '-€1.234,56 · Albert Heijn · Revolut · Short-term Spends');

        $transaction = Transaction::sole();
        $this->assertSame('-1234.56', $transaction->amount);
        $this->assertSame('shortcut', $transaction->source);
        $this->assertSame('Weekly groceries', $transaction->notes);
        $this->assertTrue($transaction->account->is(Account::where('name', 'revolut')->sole()));
        $this->assertSame('2026-07-15', $transaction->period->start_date->toDateString());
        $this->assertSame('2026-08-14', $transaction->period->end_date->toDateString());
    }

    public function test_it_defaults_to_an_uncategorized_expense_dated_today(): void
    {
        $this->travelTo('2026-10-05 09:00:00');

        $response = $this->withToken(self::TOKEN)->postJson(route('api.transactions.store'), [
            'amount' => 4.5,
            'description' => 'Coffee',
            'account' => 'amex',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', -4.5)
            ->assertJsonPath('data.date', '2026-10-05')
            ->assertJsonPath('data.category', null)
            ->assertJsonPath('data.period', '15 Sep – 14 Oct')
            ->assertJsonPath('message', '-€4,50 · Coffee · American Express · Uncategorized');
    }

    public function test_income_is_stored_as_a_positive_amount(): void
    {
        $this->withToken(self::TOKEN)->postJson(route('api.transactions.store'), [
            'amount' => '-80,00',
            'description' => 'Tikkie from Sam',
            'account' => 'Rabobank',
            'type' => 'income',
        ])->assertCreated()->assertJsonPath('data.amount', 80);
    }

    public function test_wallet_card_names_resolve_to_the_matching_account(): void
    {
        $this->withToken(self::TOKEN)->postJson(route('api.transactions.store'), [
            'amount' => '12,00',
            'description' => 'Cinema',
            'account' => 'American Express Gold Card',
        ])->assertCreated()->assertJsonPath('data.account', 'American Express');
    }

    public function test_it_reuses_an_existing_period(): void
    {
        $period = Period::create(['start_date' => '2026-09-15', 'end_date' => '2026-10-14', 'is_current' => true]);

        $this->withToken(self::TOKEN)->postJson(route('api.transactions.store'), [
            'amount' => '10',
            'description' => 'Lunch',
            'account' => 'Revolut',
            'date' => '2026-10-01',
        ])->assertCreated();

        $this->assertSame(1, Period::count());
        $this->assertTrue(Transaction::sole()->period->is($period));
    }

    public function test_it_rejects_requests_without_a_valid_token(): void
    {
        $payload = ['amount' => '10', 'description' => 'Lunch', 'account' => 'Revolut'];

        $this->postJson(route('api.transactions.store'), $payload)->assertUnauthorized();
        $this->withToken('wrong-token')->postJson(route('api.transactions.store'), $payload)->assertUnauthorized();
        $this->withoutToken()->getJson(route('api.options'))->assertUnauthorized();

        $this->assertSame(0, Transaction::count());
    }

    public function test_the_api_is_disabled_when_no_token_is_configured(): void
    {
        config(['services.shortcuts.token' => null]);

        $this->withToken('')->postJson(route('api.transactions.store'), [
            'amount' => '10',
            'description' => 'Lunch',
            'account' => 'Revolut',
        ])->assertUnauthorized();
    }

    public function test_validation_errors_are_returned_as_json_even_without_an_accept_header(): void
    {
        $this->withToken(self::TOKEN)->post(route('api.transactions.store'), [
            'amount' => '€ 0,00',
            'description' => '',
            'account' => 'ING',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'description', 'account']);
    }

    public function test_unknown_or_archived_categories_are_rejected(): void
    {
        Category::where('name', 'Savings')->update(['is_archived' => true]);

        foreach (['Groceries', 'Savings'] as $category) {
            $this->withToken(self::TOKEN)->postJson(route('api.transactions.store'), [
                'amount' => '10',
                'description' => 'Lunch',
                'account' => 'Revolut',
                'category' => $category,
            ])->assertJsonValidationErrors(['category']);
        }

        $this->assertSame(0, Transaction::count());
    }

    public function test_options_lists_accounts_and_active_categories(): void
    {
        Category::where('name', 'Savings')->update(['is_archived' => true]);

        $this->withToken(self::TOKEN)->getJson(route('api.options'))
            ->assertOk()
            ->assertExactJson([
                'accounts' => ['American Express', 'Rabobank', 'Revolut'],
                'categories' => ['Fixed Costs', 'Long-term Spends', 'Short-term Spends', 'Investments'],
            ]);
    }
}
