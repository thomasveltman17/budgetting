<?php

namespace App\Services;

use App\Models\NetWorthAccount;
use App\Models\NetWorthAccountPeriod;
use App\Models\Period;

class NetWorthPeriodService
{
    public function initializePeriodAccounts(Period $period): void
    {
        $previousPeriod = Period::where('start_date', '<', $period->start_date)
            ->orderByDesc('start_date')
            ->first();

        $accounts = NetWorthAccount::all();

        foreach ($accounts as $account) {
            $isArchived = false;

            if ($previousPeriod) {
                $previousPeriodRecord = NetWorthAccountPeriod::where('net_worth_account_id', $account->id)
                    ->where('period_id', $previousPeriod->id)
                    ->first();

                $isArchived = $previousPeriodRecord?->is_archived ?? false;
            }

            NetWorthAccountPeriod::firstOrCreate(
                [
                    'net_worth_account_id' => $account->id,
                    'period_id' => $period->id,
                ],
                ['is_archived' => $isArchived]
            );
        }
    }

    public function toggleArchiveForPeriod(NetWorthAccount $account, Period $period): bool
    {
        $record = NetWorthAccountPeriod::where('net_worth_account_id', $account->id)
            ->where('period_id', $period->id)
            ->first();

        if (! $record) {
            $record = NetWorthAccountPeriod::create([
                'net_worth_account_id' => $account->id,
                'period_id' => $period->id,
                'is_archived' => false,
            ]);
        }

        $record->update(['is_archived' => ! $record->is_archived]);

        return $record->is_archived;
    }
}
