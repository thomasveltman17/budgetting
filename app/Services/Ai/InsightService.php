<?php

namespace App\Services\Ai;

use App\Models\Period;

/**
 * Notable things about the current period, worked out from the data alone so
 * the assistant can open with them before anything is asked. Each insight
 * carries a question to hand to the assistant for the details.
 */
class InsightService
{
    public function __construct(private FinanceData $data) {}

    /**
     * @return list<array{tone: string, title: string, text: string, prompt: string}>
     */
    public function forCurrentPeriod(int $limit = 4): array
    {
        $period = $this->data->resolvePeriod('current');
        $overview = $this->data->overview($period);
        $previous = $overview['previous_period'] ?? null;

        $insights = array_filter([
            $this->pace($overview, $previous),
            $this->budget($period),
            $this->categoryAhead($period, $previous),
            $this->uncategorized($overview),
            $this->biggestExpense($period),
        ]);

        return array_slice(array_values($insights), 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $overview
     * @param  array<string, mixed>|null  $previous
     * @return array{tone: string, title: string, text: string, prompt: string}|null
     */
    private function pace(array $overview, ?array $previous): ?array
    {
        $projected = $overview['projected_spending'];

        if ($projected === null || ! $previous || $previous['spending'] <= 0 || $overview['days_elapsed'] < 3) {
            return null;
        }

        $difference = $projected - $previous['spending'];

        if (abs($difference) < max(50, $previous['spending'] * 0.1)) {
            return null;
        }

        return $difference > 0
            ? [
                'tone' => 'warning',
                'title' => 'Spending faster than last period',
                'text' => 'At this pace you will spend about '.$this->money($projected).', '.$this->money($difference).' more than last period.',
                'prompt' => 'Why am I spending more than last period, and what can I do about it?',
            ]
            : [
                'tone' => 'positive',
                'title' => 'Spending less than last period',
                'text' => 'At this pace you will spend about '.$this->money($projected).', '.$this->money(-$difference).' less than last period.',
                'prompt' => 'Where am I spending less than last period?',
            ];
    }

    /**
     * @return array{tone: string, title: string, text: string, prompt: string}|null
     */
    private function budget(Period $period): ?array
    {
        $target = collect($this->data->budgetStatus($period)['targets'])
            ->first(fn (array $target) => in_array($target['status'], ['over', 'near_limit'], true));

        if (! $target) {
            return null;
        }

        $isOver = $target['status'] === 'over';

        return [
            'tone' => $isOver ? 'warning' : 'insight',
            'title' => $isOver ? "{$target['category']} is over target" : "{$target['category']} is close to its target",
            'text' => $this->money($target['spent']).' of '.$this->money($target['target']).' used ('.round($target['pct_used']).'%).',
            'prompt' => "What did I spend on {$target['category']} this period?",
        ];
    }

    /**
     * A category that has already passed what it cost in the whole previous period.
     *
     * @param  array<string, mixed>|null  $previous
     * @return array{tone: string, title: string, text: string, prompt: string}|null
     */
    private function categoryAhead(Period $period, ?array $previous): ?array
    {
        if (! $previous) {
            return null;
        }

        $before = collect($this->data->spendingByCategory($this->data->resolvePeriod((string) $previous['period']['id']))['categories'])
            ->pluck('spent', 'category');

        $category = collect($this->data->spendingByCategory($period)['categories'])
            ->filter(fn (array $row) => $row['category'] !== 'Uncategorized')
            ->map(fn (array $row) => $row + ['before' => (float) ($before[$row['category']] ?? 0)])
            ->filter(fn (array $row) => max(25, $row['before'] * 0.25) <= $row['spent'] - $row['before'])
            ->sortByDesc(fn (array $row) => $row['spent'] - $row['before'])
            ->first();

        if (! $category) {
            return null;
        }

        return [
            'tone' => 'insight',
            'title' => "{$category['category']} is up",
            'text' => $this->money($category['spent']).' so far, against '.$this->money($category['before']).' in all of last period.',
            'prompt' => "Why is {$category['category']} higher than last period?",
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return array{tone: string, title: string, text: string, prompt: string}|null
     */
    private function uncategorized(array $overview): ?array
    {
        if ($overview['uncategorized_count'] === 0) {
            return null;
        }

        $count = $overview['uncategorized_count'];

        return [
            'tone' => 'tip',
            'title' => $count === 1 ? '1 expense without a category' : "{$count} expenses without a category",
            'text' => $this->money($overview['uncategorized_amount']).' is not counted in any category yet.',
            'prompt' => 'Which expenses are still uncategorized, and which categories would fit them?',
        ];
    }

    /**
     * @return array{tone: string, title: string, text: string, prompt: string}|null
     */
    private function biggestExpense(Period $period): ?array
    {
        $transaction = $this->data->topTransactions($period, 1)['transactions'][0] ?? null;

        if (! $transaction) {
            return null;
        }

        $amount = $transaction['net_amount_after_repayments'] ?? $transaction['amount'];

        return [
            'tone' => 'insight',
            'title' => 'Biggest expense: '.$transaction['merchant'],
            'text' => $this->money(abs($amount)).' on '.date('j M', strtotime($transaction['date'])).'.',
            'prompt' => 'What were my three biggest expenses this period?',
        ];
    }

    private function money(float $amount): string
    {
        return '€ '.number_format($amount, 2, ',', '.');
    }
}
