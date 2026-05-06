<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(): View
    {
        $accountOrder = ['rabobank' => 0, 'revolut' => 1, 'amex' => 2];

        $periods = Period::where('is_current', false)
            ->orderByDesc('start_date')
            ->with(['transactions.account', 'transactions.category'])
            ->get()
            ->map(function (Period $period) use ($accountOrder): array {
                $expenses = $period->transactions->where('amount', '<', 0);

                $accountSummaries = $expenses
                    ->groupBy('account_id')
                    ->map(fn ($txs) => [
                        'label' => $txs->first()->account->label,
                        'name' => $txs->first()->account->name,
                        'spent' => abs((float) $txs->sum('amount')),
                    ])
                    ->sortBy(fn ($item) => $accountOrder[$item['name']] ?? 99)
                    ->values();

                $categorySummaries = $expenses
                    ->whereNotNull('category_id')
                    ->groupBy('category_id')
                    ->map(fn ($txs) => [
                        'name' => $txs->first()->category->name,
                        'color' => $txs->first()->category->color,
                        'sort_order' => $txs->first()->category->sort_order,
                        'spent' => abs((float) $txs->sum('amount')),
                    ])
                    ->sortBy('sort_order')
                    ->values();

                $uncategorizedSpent = abs((float) $expenses->whereNull('category_id')->sum('amount'));

                return [
                    'period' => $period,
                    'accountSummaries' => $accountSummaries,
                    'categorySummaries' => $categorySummaries,
                    'uncategorizedSpent' => $uncategorizedSpent,
                    'totalSpent' => abs((float) $expenses->sum('amount')),
                    'transactionCount' => $period->transactions->count(),
                ];
            });

        return view('history', compact('periods'));
    }

    public function viewPeriodTransactions(Period $period): RedirectResponse
    {
        app(PeriodService::class)->switchPeriod($period->id);

        return redirect()->route('transactions');
    }
}
