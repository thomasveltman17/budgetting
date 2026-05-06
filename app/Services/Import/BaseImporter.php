<?php

namespace App\Services\Import;

use App\Models\Period;
use App\Models\Transaction;
use Carbon\Carbon;

abstract class BaseImporter
{
    abstract public function import(string $filePath, int $accountId): ImportResult;

    protected function findOrCreatePeriod(Carbon $date): Period
    {
        $period = Period::where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if ($period) {
            return $period;
        }

        // Calculate the period boundaries: 15th to 14th
        if ($date->day >= 15) {
            $start = $date->copy()->startOfMonth()->addDays(14);
            $end = $date->copy()->addMonth()->startOfMonth()->addDays(13);
        } else {
            $start = $date->copy()->subMonth()->startOfMonth()->addDays(14);
            $end = $date->copy()->startOfMonth()->addDays(13);
        }

        return Period::create([
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'is_current' => false,
        ]);
    }

    protected function generateHash(int $accountId, string $date, string $amount, string $description): string
    {
        return md5($accountId.$date.$amount.$description);
    }

    protected function isDuplicate(string $hash): bool
    {
        return Transaction::where('import_hash', $hash)->exists();
    }
}
