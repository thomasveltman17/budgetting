<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'is_current',
        'amex_paid_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'amex_paid_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgetTargets(): HasMany
    {
        return $this->hasMany(BudgetTarget::class);
    }

    public function netWorthAccountPeriods(): HasMany
    {
        return $this->hasMany(NetWorthAccountPeriod::class);
    }

    public function activeNetWorthAccounts(): BelongsToMany
    {
        return $this->belongsToMany(NetWorthAccount::class, 'net_worth_account_periods')
            ->where('is_archived', false)
            ->orderBy('sort_order');
    }
}
