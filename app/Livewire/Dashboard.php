<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\BudgetTarget;
use App\Models\Category;
use App\Models\NetWorthAccount;
use App\Models\NetWorthSnapshot;
use App\Models\Period;
use App\Models\Transaction;
use App\Services\PeriodService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $showAmexModal = false;

    public string $amexPayDate = '';

    #[Computed]
    public function period(): Period
    {
        return app(PeriodService::class)->getSelectedPeriod();
    }

    #[Computed]
    public function uncategorizedCount(): int
    {
        return Transaction::where('period_id', $this->period->id)
            ->whereNull('parent_transaction_id')
            ->whereNull('category_id')
            ->where('amount', '<', 0)
            ->count();
    }

    #[Computed]
    public function accountSummaries(): Collection
    {
        $periodId = $this->period->id;

        $order = ['rabobank' => 0, 'revolut' => 1, 'amex' => 2];

        return Account::with(['transactions' => fn ($q) => $q
            ->where('period_id', $periodId)
            ->whereNull('parent_transaction_id')
            ->with('repayments'),
        ])
            ->get()
            ->sortBy(fn ($account) => $order[$account->name] ?? 99)
            ->values()
            ->map(fn ($account) => [
                'account' => $account,
                'totalSpent' => abs((float) $account->transactions
                    ->where('amount', '<', 0)
                    ->sum(fn ($t) => $t->amount + $t->repayments->sum('amount'))),
                'transactionCount' => $account->transactions->count(),
                'uncategorizedCount' => $account->transactions->whereNull('category_id')->where('amount', '<', 0)->count(),
            ]);
    }

    #[Computed]
    public function categoryProgress(): Collection
    {
        $periodId = $this->period->id;
        $transactions = Transaction::where('period_id', $periodId)
            ->whereNull('parent_transaction_id')
            ->with('repayments')
            ->get();
        $targets = BudgetTarget::where('period_id', $periodId)->get()->keyBy('category_id');

        return Category::where('is_archived', false)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) use ($transactions, $targets) {
                $spent = abs((float) $transactions
                    ->where('category_id', $category->id)
                    ->where('amount', '<', 0)
                    ->sum(fn ($t) => $t->amount + $t->repayments->sum('amount')));

                $target = $targets->has($category->id)
                    ? (float) $targets->get($category->id)->amount
                    : null;

                $percentage = ($target !== null && $target > 0)
                    ? round(($spent / $target) * 100, 1)
                    : null;

                return [
                    'category' => $category,
                    'spent' => $spent,
                    'target' => $target,
                    'percentage' => $percentage,
                ];
            });
    }

    #[Computed]
    public function amexSplit(): array
    {
        $amexAccount = Account::where('name', 'amex')->first();

        if (! $amexAccount) {
            return ['hasTransactions' => false];
        }

        $transactions = Transaction::where('period_id', $this->period->id)
            ->where('account_id', $amexAccount->id)
            ->whereNull('parent_transaction_id')
            ->with(['category', 'repayments'])
            ->get();

        if ($transactions->isEmpty()) {
            return ['hasTransactions' => false];
        }

        $spentByCategory = $transactions
            ->groupBy(fn ($t) => $t->category?->name ?? '__uncategorized__')
            ->map(fn ($group) => abs((float) $group->where('amount', '<', 0)->sum(fn ($t) => $t->amount + $t->repayments->sum('amount'))));

        $lines = [
            ['label' => 'Short-term Spends', 'payFrom' => 'Revolut (short-term)', 'amount' => (float) $spentByCategory->get('Short-term Spends', 0)],
            ['label' => 'Long-term Spends', 'payFrom' => 'Revolut (long-term)', 'amount' => (float) $spentByCategory->get('Long-term Spends', 0)],
            ['label' => 'Fixed Costs', 'payFrom' => 'Rabobank', 'amount' => (float) $spentByCategory->get('Fixed Costs', 0)],
        ];

        $uncategorizedAmount = (float) $spentByCategory->get('__uncategorized__', 0);

        if ($uncategorizedAmount > 0) {
            $lines[] = ['label' => 'Uncategorized', 'payFrom' => '—', 'amount' => $uncategorizedAmount];
        }

        return [
            'hasTransactions' => true,
            'lines' => $lines,
            'total' => (float) array_sum(array_column($lines, 'amount')),
        ];
    }

    #[Computed]
    public function netWorthAccounts(): Collection
    {
        return NetWorthAccount::where('is_active', true)
            ->orderBy('sort_order')
            ->with('latestSnapshot')
            ->get();
    }

    #[Computed]
    public function netWorthTotal(): float
    {
        return (float) $this->netWorthAccounts
            ->map(fn ($account) => (float) ($account->latestSnapshot?->balance ?? 0))
            ->sum();
    }

    public function openAmexModal(): void
    {
        $this->amexPayDate = $this->period->end_date->copy()->addDays(2)->format('Y-m-d');
        $this->resetValidation();
        $this->showAmexModal = true;
    }

    public function closeAmexModal(): void
    {
        $this->showAmexModal = false;
        $this->resetValidation();
    }

    public function markAmexPaid(): void
    {
        $this->validate([
            'amexPayDate' => ['required', 'date'],
        ]);

        $this->period->update(['amex_paid_at' => $this->amexPayDate]);

        $this->showAmexModal = false;
        unset($this->period, $this->amexSplit);

        $this->dispatch('toast', type: 'success', message: 'AmEx marked as paid.');
    }

    public function updateNetWorthBalance(int $accountId, string $balance): void
    {
        $parsedBalance = (float) str_replace(',', '.', $balance);

        NetWorthAccount::findOrFail($accountId);

        NetWorthSnapshot::create([
            'net_worth_account_id' => $accountId,
            'balance' => $parsedBalance,
            'recorded_at' => now(),
        ]);

        unset($this->netWorthAccounts, $this->netWorthTotal);

        $this->dispatch('toast', type: 'success', message: 'Balance updated.');
    }

    public function render(): View
    {
        return view('livewire.dashboard', [
            'period' => $this->period,
            'uncategorizedCount' => $this->uncategorizedCount,
            'accountSummaries' => $this->accountSummaries,
            'categoryProgress' => $this->categoryProgress,
            'amexSplit' => $this->amexSplit,
            'netWorthAccounts' => $this->netWorthAccounts,
            'netWorthTotal' => $this->netWorthTotal,
        ]);
    }
}
