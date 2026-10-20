<?php

namespace App\Services\Ai;

use App\Models\Period;
use InvalidArgumentException;

/**
 * The read-only tools the model can call to look up the user's finances.
 * Every tool returns plain arrays; bad arguments come back as an error the
 * model can correct instead of an exception.
 */
class FinanceTools
{
    private const PERIOD = [
        'type' => 'string',
        'description' => 'Budget period: "current" (default), "previous", a period id from list_periods, or a date (YYYY-MM-DD) inside the period.',
    ];

    private const SCOPE = [
        'type' => 'string',
        'enum' => ['spending', 'all'],
        'description' => '"spending" (default) leaves out transfers to savings and investment categories; "all" counts every outflow.',
    ];

    public function __construct(private FinanceData $data) {}

    /**
     * @return list<array{name: string, description: string, parameters?: array<string, mixed>}>
     */
    public function definitions(): array
    {
        return [
            [
                'name' => 'get_period_overview',
                'description' => 'Headline numbers for one budget period: income, spending, money saved or invested, net, uncategorized expenses, daily average, projected spending for the current period, and a comparison with the previous period.',
                'parameters' => $this->object(['period' => self::PERIOD]),
            ],
            [
                'name' => 'spending_by_category',
                'description' => 'Spending per category in a period, largest first, with share of total and budget target if one is set.',
                'parameters' => $this->object(['period' => self::PERIOD, 'scope' => self::SCOPE]),
            ],
            [
                'name' => 'top_transactions',
                'description' => 'The largest expenses in a period, optionally within one category or account. Amounts are net of linked repayments.',
                'parameters' => $this->object([
                    'period' => self::PERIOD,
                    'limit' => ['type' => 'integer', 'description' => 'How many to return, 1 to 25. Default 5.'],
                    'category' => ['type' => 'string', 'description' => 'Category name, or "uncategorized".'],
                    'account' => ['type' => 'string', 'description' => 'Account name, e.g. Rabobank, Revolut, American Express.'],
                    'scope' => self::SCOPE,
                ]),
            ],
            [
                'name' => 'top_merchants',
                'description' => 'Where money goes: spending grouped by merchant in a period, largest first, with transaction ids.',
                'parameters' => $this->object([
                    'period' => self::PERIOD,
                    'limit' => ['type' => 'integer', 'description' => 'How many merchants, 1 to 25. Default 10.'],
                    'scope' => self::SCOPE,
                ]),
            ],
            [
                'name' => 'search_transactions',
                'description' => 'Find transactions across all periods by text in the description or notes, date range, amount range, category, account or direction. Includes repayments. Use it to explain specific transactions.',
                'parameters' => $this->object([
                    'query' => ['type' => 'string', 'description' => 'Text to look for in the description or notes.'],
                    'date_from' => ['type' => 'string', 'description' => 'Earliest date, YYYY-MM-DD.'],
                    'date_to' => ['type' => 'string', 'description' => 'Latest date, YYYY-MM-DD.'],
                    'min_amount' => ['type' => 'number', 'description' => 'Smallest absolute amount in euros.'],
                    'max_amount' => ['type' => 'number', 'description' => 'Largest absolute amount in euros.'],
                    'direction' => ['type' => 'string', 'enum' => ['expense', 'income', 'any'], 'description' => 'Default "any".'],
                    'category' => ['type' => 'string', 'description' => 'Category name, or "uncategorized".'],
                    'account' => ['type' => 'string', 'description' => 'Account name.'],
                    'uncategorized_only' => ['type' => 'boolean', 'description' => 'Only transactions without a category.'],
                    'sort' => ['type' => 'string', 'enum' => ['date', 'amount'], 'description' => 'Newest first (default) or largest first.'],
                    'limit' => ['type' => 'integer', 'description' => 'How many to return, 1 to 50. Default 20.'],
                ]),
            ],
            [
                'name' => 'budget_status',
                'description' => 'Budget targets for a period with amount spent, remaining and status, plus how much of the period has passed.',
                'parameters' => $this->object(['period' => self::PERIOD]),
            ],
            [
                'name' => 'spending_trend',
                'description' => 'Spending and income per budget period over recent periods, oldest first, optionally for one category.',
                'parameters' => $this->object([
                    'periods' => ['type' => 'integer', 'description' => 'How many periods back, including the current one, 2 to 12. Default 6.'],
                    'category' => ['type' => 'string', 'description' => 'Category name, or "uncategorized".'],
                    'scope' => self::SCOPE,
                ]),
            ],
            [
                'name' => 'list_periods',
                'description' => 'Recent budget periods with their ids, dates and number of transactions.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function call(string $name, array $args): array
    {
        try {
            return match ($name) {
                'get_period_overview' => $this->data->overview($this->period($args)),
                'spending_by_category' => $this->data->spendingByCategory($this->period($args), $this->scope($args)),
                'top_transactions' => $this->data->topTransactions(
                    $this->period($args),
                    $this->integer($args, 'limit', 5, 1, 25),
                    $this->string($args, 'category'),
                    $this->string($args, 'account'),
                    $this->scope($args),
                ),
                'top_merchants' => $this->data->topMerchants($this->period($args), $this->integer($args, 'limit', 10, 1, 25), $this->scope($args)),
                'search_transactions' => $this->data->searchTransactions([
                    'query' => $this->string($args, 'query'),
                    'date_from' => $this->string($args, 'date_from'),
                    'date_to' => $this->string($args, 'date_to'),
                    'min_amount' => is_numeric($args['min_amount'] ?? null) ? (float) $args['min_amount'] : null,
                    'max_amount' => is_numeric($args['max_amount'] ?? null) ? (float) $args['max_amount'] : null,
                    'direction' => $this->string($args, 'direction') ?? 'any',
                    'category' => $this->string($args, 'category'),
                    'account' => $this->string($args, 'account'),
                    'uncategorized_only' => (bool) ($args['uncategorized_only'] ?? false),
                    'sort' => $this->string($args, 'sort') ?? 'date',
                    'limit' => $this->integer($args, 'limit', 20, 1, 50),
                ]),
                'budget_status' => $this->data->budgetStatus($this->period($args)),
                'spending_trend' => $this->data->spendingTrend(
                    $this->integer($args, 'periods', 6, 2, 12),
                    $this->string($args, 'category'),
                    $this->scope($args),
                ),
                'list_periods' => ['periods' => $this->data->listPeriods()],
                default => ['error' => "Unknown tool \"{$name}\"."],
            };
        } catch (InvalidArgumentException $exception) {
            return ['error' => $exception->getMessage()];
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @return array<string, mixed>
     */
    private function object(array $properties): array
    {
        return ['type' => 'object', 'properties' => $properties];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function period(array $args): Period
    {
        return $this->data->resolvePeriod($this->string($args, 'period'));
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function scope(array $args): string
    {
        return ($args['scope'] ?? null) === 'all' ? 'all' : 'spending';
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function string(array $args, string $key): ?string
    {
        $value = $args[$key] ?? null;

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function integer(array $args, string $key, int $default, int $min, int $max): int
    {
        $value = $args[$key] ?? null;

        return is_numeric($value) ? max($min, min($max, (int) $value)) : $default;
    }
}
