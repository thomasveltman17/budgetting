<?php

namespace App\Services\Ai;

use App\Models\Account;
use App\Models\BudgetTarget;
use App\Models\Category;
use App\Models\Period;
use App\Models\Transaction;
use App\Services\PeriodService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Read-only financial figures for the assistant. Spending follows the
 * dashboard's rules: top-level transactions only, pending returns left out,
 * and every expense counted net of the repayments linked to it.
 *
 * The "spending" scope leaves out money moved to savings and investment
 * categories; "all" counts every outflow, like the dashboard's expenses.
 */
class FinanceData
{
    public const GOAL_CATEGORY_TYPES = ['savings', 'investment'];

    public function __construct(private PeriodService $periods) {}

    /**
     * Resolve "current", "previous", a period id or a date inside a period.
     */
    public function resolvePeriod(?string $reference): Period
    {
        $reference = trim((string) $reference);
        $current = $this->periods->ensureCurrentPeriodExists();

        if ($reference === '' || $reference === 'current') {
            return $current;
        }

        if ($reference === 'previous') {
            return $this->previousPeriod($current)
                ?? throw new InvalidArgumentException('There is no period before the current one.');
        }

        if (ctype_digit($reference)) {
            return Period::find((int) $reference)
                ?? throw new InvalidArgumentException("No period with id {$reference}. Call list_periods for valid ids.");
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $reference)) {
            return Period::whereDate('start_date', '<=', $reference)
                ->whereDate('end_date', '>=', $reference)
                ->first()
                ?? throw new InvalidArgumentException("No period contains {$reference}.");
        }

        throw new InvalidArgumentException("Unknown period \"{$reference}\". Use \"current\", \"previous\", a period id or a date (YYYY-MM-DD).");
    }

    public function previousPeriod(Period $period): ?Period
    {
        return Period::where('start_date', '<', $period->start_date)
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * @return array{id: int, label: string, start: string, end: string, is_current: bool}
     */
    public function describePeriod(Period $period): array
    {
        return [
            'id' => $period->id,
            'label' => $period->start_date->format('j M').' – '.$period->end_date->format('j M Y'),
            'start' => $period->start_date->toDateString(),
            'end' => $period->end_date->toDateString(),
            'is_current' => (bool) $period->is_current,
        ];
    }

    /**
     * @return list<array{id: int, label: string, start: string, end: string, is_current: bool, transaction_count: int}>
     */
    public function listPeriods(int $limit = 12): array
    {
        return Period::withCount('transactions')
            ->orderByDesc('start_date')
            ->limit($limit)
            ->get()
            ->map(fn (Period $period) => $this->describePeriod($period) + ['transaction_count' => $period->transactions_count])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(Period $period): array
    {
        $transactions = $this->periodTransactions($period);
        $expenses = $transactions->where('amount', '<', 0);

        $outflows = $this->sumSpent($expenses);
        $spending = $this->sumSpent($expenses->filter(fn (Transaction $t) => ! $this->isGoalTransaction($t)));
        $income = round((float) $transactions->where('amount', '>', 0)->sum('amount'), 2);
        $uncategorized = $expenses->whereNull('category_id');

        $daysTotal = $period->lengthInDays();
        $daysElapsed = $period->elapsedDays();

        $overview = [
            'period' => $this->describePeriod($period),
            'income' => $income,
            'spending' => $spending,
            'saved_and_invested' => round($outflows - $spending, 2),
            'all_outflows' => $outflows,
            'net' => round($income - $outflows, 2),
            'transaction_count' => $transactions->count(),
            'uncategorized_count' => $uncategorized->count(),
            'uncategorized_amount' => $this->sumSpent($uncategorized),
            'days_total' => $daysTotal,
            'days_elapsed' => $daysElapsed,
            'average_spending_per_day' => $daysElapsed > 0 ? round($spending / $daysElapsed, 2) : null,
            'projected_spending' => ($daysElapsed > 0 && $daysElapsed < $daysTotal) ? round($spending / $daysElapsed * $daysTotal, 2) : null,
        ];

        $previous = $this->previousPeriod($period);

        if ($previous) {
            $previousTransactions = $this->periodTransactions($previous);
            $previousSpending = $this->sumSpent($previousTransactions
                ->where('amount', '<', 0)
                ->filter(fn (Transaction $t) => ! $this->isGoalTransaction($t)));

            $overview['previous_period'] = [
                'period' => $this->describePeriod($previous),
                'income' => round((float) $previousTransactions->where('amount', '>', 0)->sum('amount'), 2),
                'spending' => $previousSpending,
                'spending_change_pct' => $previousSpending > 0 ? round(($spending - $previousSpending) / $previousSpending * 100, 1) : null,
            ];
        }

        return $overview;
    }

    /**
     * @return array{period: array<string, mixed>, scope: string, total: float, categories: list<array<string, mixed>>}
     */
    public function spendingByCategory(Period $period, string $scope = 'spending'): array
    {
        $expenses = $this->scopedExpenses($this->periodTransactions($period), $scope);
        $total = $this->sumSpent($expenses);
        $targets = BudgetTarget::where('period_id', $period->id)->pluck('amount', 'category_id');

        $categories = $expenses
            ->groupBy(fn (Transaction $t) => $t->category_id ?? 0)
            ->map(function (Collection $group, int $categoryId) use ($total, $targets) {
                $category = $group->first()->category;
                $spent = $this->sumSpent($group);
                $target = $categoryId && isset($targets[$categoryId]) ? (float) $targets[$categoryId] : null;

                return [
                    'category' => $category?->name ?? 'Uncategorized',
                    'type' => $category?->type,
                    'color' => $category?->color,
                    'spent' => $spent,
                    'share_pct' => $total > 0 ? round($spent / $total * 100, 1) : 0,
                    'transaction_count' => $group->count(),
                    'target' => $target,
                    'pct_of_target' => $target ? round($spent / $target * 100, 1) : null,
                ];
            })
            ->sortByDesc('spent')
            ->values()
            ->all();

        return [
            'period' => $this->describePeriod($period),
            'scope' => $scope,
            'total' => $total,
            'categories' => $categories,
        ];
    }

    /**
     * @return array{period: array<string, mixed>, scope: string, transactions: list<array<string, mixed>>}
     */
    public function topTransactions(Period $period, int $limit = 5, ?string $category = null, ?string $account = null, string $scope = 'spending'): array
    {
        $expenses = $this->filterByCategoryAndAccount(
            $this->scopedExpenses($this->periodTransactions($period), $scope),
            $category,
            $account,
        );

        return [
            'period' => $this->describePeriod($period),
            'scope' => $scope,
            'transactions' => $expenses
                ->sortByDesc(fn (Transaction $t) => $this->spentAmount($t))
                ->take($limit)
                ->map($this->transactionRow(...))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{period: array<string, mixed>, scope: string, merchants: list<array{merchant: string, spent: float, transaction_count: int, transaction_ids: list<int>}>}
     */
    public function topMerchants(Period $period, int $limit = 10, string $scope = 'spending'): array
    {
        $merchants = $this->scopedExpenses($this->periodTransactions($period), $scope)
            ->groupBy(fn (Transaction $t) => mb_strtolower($t->merchant_name))
            ->map(fn (Collection $group) => [
                'merchant' => $group->first()->merchant_name,
                'spent' => $this->sumSpent($group),
                'transaction_count' => $group->count(),
                'transaction_ids' => $group->pluck('id')->take(10)->values()->all(),
            ])
            ->sortByDesc('spent')
            ->take($limit)
            ->values()
            ->all();

        return [
            'period' => $this->describePeriod($period),
            'scope' => $scope,
            'merchants' => $merchants,
        ];
    }

    /**
     * Search all transactions, including repayments, across every period.
     *
     * @param  array{query?: ?string, date_from?: ?string, date_to?: ?string, min_amount?: ?float, max_amount?: ?float, direction?: ?string, category?: ?string, account?: ?string, uncategorized_only?: bool, sort?: ?string, limit?: int}  $filters
     * @return array{total_matched: int, total_amount: float, transactions: list<array<string, mixed>>}
     */
    public function searchTransactions(array $filters): array
    {
        $query = Transaction::query()->with(['account', 'category', 'repayments']);

        if (filled($filters['query'] ?? null)) {
            $term = '%'.$filters['query'].'%';
            $query->where(fn ($q) => $q->where('description', 'like', $term)->orWhere('notes', 'like', $term));
        }

        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('date', '>=', $this->parseDate($filters['date_from']));
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('date', '<=', $this->parseDate($filters['date_to']));
        }

        if (isset($filters['min_amount'])) {
            $query->whereRaw('ABS(amount) >= CAST(? AS DECIMAL(12,2))', [abs((float) $filters['min_amount'])]);
        }

        if (isset($filters['max_amount'])) {
            $query->whereRaw('ABS(amount) <= CAST(? AS DECIMAL(12,2))', [abs((float) $filters['max_amount'])]);
        }

        match ($filters['direction'] ?? 'any') {
            'expense' => $query->where('amount', '<', 0),
            'income' => $query->where('amount', '>', 0),
            default => null,
        };

        if ($filters['uncategorized_only'] ?? false) {
            $query->whereNull('category_id');
        } elseif (filled($filters['category'] ?? null)) {
            $category = $this->findCategory($filters['category']);
            $category ? $query->where('category_id', $category->id) : $query->whereNull('category_id');
        }

        if (filled($filters['account'] ?? null)) {
            $query->where('account_id', $this->findAccount($filters['account'])->id);
        }

        $matches = $query->get();
        $sorted = ($filters['sort'] ?? 'date') === 'amount'
            ? $matches->sortByDesc(fn (Transaction $t) => abs((float) $t->amount))
            : $matches->sortByDesc(fn (Transaction $t) => $t->date->format('Ymd').str_pad((string) $t->id, 10, '0', STR_PAD_LEFT));

        return [
            'total_matched' => $matches->count(),
            'total_amount' => round((float) $matches->sum('amount'), 2),
            'transactions' => $sorted->take($filters['limit'] ?? 20)->map($this->transactionRow(...))->values()->all(),
        ];
    }

    /**
     * @return array{period: array<string, mixed>, period_elapsed_pct: float, targets: list<array<string, mixed>>}
     */
    public function budgetStatus(Period $period): array
    {
        $expenses = $this->periodTransactions($period)->where('amount', '<', 0);
        $elapsedPct = round($period->elapsedDays() / $period->lengthInDays() * 100, 1);

        $targets = BudgetTarget::with('category')
            ->where('period_id', $period->id)
            ->get()
            ->filter(fn (BudgetTarget $target) => $target->category !== null)
            ->map(function (BudgetTarget $target) use ($expenses) {
                $spent = $this->sumSpent($expenses->where('category_id', $target->category_id));
                $amount = (float) $target->amount;
                $pct = $amount > 0 ? round($spent / $amount * 100, 1) : null;
                $isGoal = in_array($target->category->type, self::GOAL_CATEGORY_TYPES, true);

                return [
                    'category' => $target->category->name,
                    'type' => $target->category->type,
                    'color' => $target->category->color,
                    'target' => $amount,
                    'spent' => $spent,
                    'remaining' => round($amount - $spent, 2),
                    'pct_used' => $pct,
                    'status' => match (true) {
                        $pct === null => 'no_target',
                        $isGoal => $pct >= 100 ? 'goal_reached' : 'goal_in_progress',
                        $pct > 100 => 'over',
                        $pct >= 80 => 'near_limit',
                        default => 'on_track',
                    },
                ];
            })
            ->sortByDesc('pct_used')
            ->values()
            ->all();

        return [
            'period' => $this->describePeriod($period),
            'period_elapsed_pct' => $elapsedPct,
            'targets' => $targets,
        ];
    }

    /**
     * @return array{scope: string, category: ?string, periods: list<array{period: array<string, mixed>, spending: float, income: float}>}
     */
    public function spendingTrend(int $count = 6, ?string $category = null, string $scope = 'spending'): array
    {
        $current = $this->periods->ensureCurrentPeriodExists();

        $periods = Period::whereDate('start_date', '<=', $current->start_date)
            ->orderByDesc('start_date')
            ->limit($count)
            ->get()
            ->reverse()
            ->values();

        return [
            'scope' => $scope,
            'category' => $category,
            'periods' => $periods->map(function (Period $period) use ($category, $scope) {
                $transactions = $this->periodTransactions($period);
                $expenses = $this->filterByCategoryAndAccount($this->scopedExpenses($transactions, $scope), $category, null);

                return [
                    'period' => $this->describePeriod($period),
                    'spending' => $this->sumSpent($expenses),
                    'income' => round((float) $transactions->where('amount', '>', 0)->sum('amount'), 2),
                ];
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transactionRow(Transaction $transaction): array
    {
        $row = [
            'id' => $transaction->id,
            'date' => $transaction->date->toDateString(),
            'merchant' => $transaction->merchant_name,
            'description' => $transaction->description,
            'amount' => round((float) $transaction->amount, 2),
            'category' => $transaction->category?->name,
            'account' => $transaction->account?->label,
        ];

        if ($transaction->relationLoaded('repayments') && $transaction->repayments->isNotEmpty()) {
            $row['net_amount_after_repayments'] = round($this->netAmount($transaction), 2);
        }

        if (filled($transaction->notes)) {
            $row['notes'] = $transaction->notes;
        }

        if ($transaction->parent_transaction_id) {
            $row['repayment_of_transaction_id'] = $transaction->parent_transaction_id;
        }

        if ($transaction->is_pending_return) {
            $row['pending_return'] = true;
        }

        return $row;
    }

    public function netAmount(Transaction $transaction): float
    {
        return (float) $transaction->amount + (float) $transaction->repayments->sum('amount');
    }

    public function spentAmount(Transaction $transaction): float
    {
        return max(0.0, -$this->netAmount($transaction));
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function periodTransactions(Period $period): Collection
    {
        return Transaction::with(['category', 'account', 'repayments'])
            ->where('period_id', $period->id)
            ->whereNull('parent_transaction_id')
            ->where('is_pending_return', false)
            ->get();
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, Transaction>
     */
    private function scopedExpenses(Collection $transactions, string $scope): Collection
    {
        return $transactions
            ->where('amount', '<', 0)
            ->filter(fn (Transaction $t) => $scope === 'all' || ! $this->isGoalTransaction($t));
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, Transaction>
     */
    private function filterByCategoryAndAccount(Collection $transactions, ?string $category, ?string $account): Collection
    {
        if (filled($category)) {
            $categoryId = $this->findCategory($category)?->id;
            $transactions = $transactions->filter(fn (Transaction $t) => $t->category_id === $categoryId);
        }

        if (filled($account)) {
            $accountId = $this->findAccount($account)->id;
            $transactions = $transactions->filter(fn (Transaction $t) => $t->account_id === $accountId);
        }

        return $transactions;
    }

    private function isGoalTransaction(Transaction $transaction): bool
    {
        return in_array($transaction->category?->type, self::GOAL_CATEGORY_TYPES, true);
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function sumSpent(Collection $transactions): float
    {
        return round((float) $transactions->sum(fn (Transaction $t) => $this->spentAmount($t)), 2);
    }

    /**
     * Find a category by name; "uncategorized" resolves to null.
     */
    private function findCategory(string $name): ?Category
    {
        if (in_array(mb_strtolower(trim($name)), ['uncategorized', 'uncategorised', 'none'], true)) {
            return null;
        }

        return Category::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first()
            ?? throw new InvalidArgumentException("Unknown category \"{$name}\". Valid categories: ".Category::orderBy('sort_order')->pluck('name')->implode(', ').', or "uncategorized".');
    }

    private function findAccount(string $name): Account
    {
        $needle = mb_strtolower(trim($name));

        return Account::whereRaw('LOWER(name) = ?', [$needle])->orWhereRaw('LOWER(label) = ?', [$needle])->first()
            ?? throw new InvalidArgumentException("Unknown account \"{$name}\". Valid accounts: ".Account::orderBy('label')->pluck('label')->implode(', ').'.');
    }

    private function parseDate(string $date): string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException("Dates must be YYYY-MM-DD, got \"{$date}\".");
        }

        return $date;
    }
}
