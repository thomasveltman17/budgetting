<?php

namespace App\Services\Import;

use App\Models\Period;
use App\Models\Transaction;
use App\Services\PeriodService;
use Carbon\Carbon;

abstract class BaseImporter
{
    abstract public function import(string $filePath, int $accountId): ImportResult;

    protected function findOrCreatePeriod(Carbon $date): Period
    {
        return app(PeriodService::class)->findOrCreateForDate($date);
    }

    protected function generateHash(int $accountId, string $date, string $amount, string $description): string
    {
        return md5($accountId.$date.$amount.$description);
    }

    protected function isDuplicate(string $hash): bool
    {
        return Transaction::withTrashed()->where('import_hash', $hash)->exists();
    }
}
