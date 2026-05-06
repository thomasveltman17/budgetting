<?php

namespace App\Services;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PeriodService
{
    public function ensureCurrentPeriodExists(): Period
    {
        ['start' => $start, 'end' => $end] = $this->calculateCurrentDates();

        $period = Period::whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->first();

        if (! $period) {
            $period = Period::create([
                'start_date' => $start,
                'end_date' => $end,
                'is_current' => true,
            ]);
        }

        if (! $period->is_current) {
            Period::where('is_current', true)->update(['is_current' => false]);
            $period->update(['is_current' => true]);
        }

        return $period;
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

    /** @return array{start: string, end: string} */
    private function calculateCurrentDates(): array
    {
        $today = Carbon::today();

        if ($today->day >= 15) {
            $start = $today->copy()->day(15);
            $end = $today->copy()->addMonth()->day(14);
        } else {
            $start = $today->copy()->subMonth()->day(15);
            $end = $today->copy()->day(14);
        }

        return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
    }
}
