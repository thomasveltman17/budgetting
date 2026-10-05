<?php

namespace App\Services;

use App\Models\Period;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PeriodService
{
    public function ensureCurrentPeriodExists(): Period
    {
        ['start' => $start, 'end' => $end] = $this->calculateDatesFor(Carbon::today());

        $period = Period::whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->first();

        if (! $period) {
            $period = Period::create([
                'start_date' => $start,
                'end_date' => $end,
                'is_current' => false,
            ]);
        }

        if (! $period->is_current) {
            Period::where('is_current', true)->update(['is_current' => false]);
            $period->update(['is_current' => true]);
        }

        return $period;
    }

    /**
     * Find the period containing the given date, creating it if needed.
     */
    public function findOrCreateForDate(CarbonInterface $date): Period
    {
        $period = Period::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($period) {
            return $period;
        }

        ['start' => $start, 'end' => $end] = $this->calculateDatesFor($date);

        return Period::create([
            'start_date' => $start,
            'end_date' => $end,
            'is_current' => false,
        ]);
    }

    public function getSelectedPeriod(): Period
    {
        $periodId = session('selected_period_id');

        if ($periodId) {
            $period = Period::find($periodId);
            if ($period) {
                return $period;
            }
        }

        return $this->ensureCurrentPeriodExists();
    }

    public function switchPeriod(int $periodId): void
    {
        session(['selected_period_id' => $periodId]);
    }

    public function getRecentPeriods(int $count = 6): Collection
    {
        return Period::orderByDesc('start_date')->limit($count)->get();
    }

    public function formatLabel(Period $period): string
    {
        return $period->start_date->format('j M').' – '.$period->end_date->format('j M');
    }

    /**
     * Periods run from the 15th up to and including the 14th of the next month.
     *
     * @return array{start: string, end: string}
     */
    private function calculateDatesFor(CarbonInterface $date): array
    {
        $monthStart = $date->copy()->startOfMonth();

        if ($date->day >= 15) {
            $start = $monthStart->copy()->day(15);
            $end = $monthStart->copy()->addMonthNoOverflow()->day(14);
        } else {
            $start = $monthStart->copy()->subMonthNoOverflow()->day(15);
            $end = $monthStart->copy()->day(14);
        }

        return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
    }
}
