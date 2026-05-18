<?php

namespace Database\Seeders;

use App\Models\NetWorthAccount;
use App\Models\NetWorthAccountPeriod;
use App\Models\Period;
use Illuminate\Database\Seeder;

class NetWorthAccountPeriodsSeeder extends Seeder
{
    public function run(): void
    {
        $periods = Period::all();
        $accounts = NetWorthAccount::all();

        foreach ($periods as $period) {
            foreach ($accounts as $account) {
                NetWorthAccountPeriod::firstOrCreate(
                    [
                        'net_worth_account_id' => $account->id,
                        'period_id' => $period->id,
                    ],
                    ['is_archived' => false]
                );
            }
        }
    }
}
