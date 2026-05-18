<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NetWorthAccount extends Model
{
    protected $fillable = [
        'name',
        'type',
        'notes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(NetWorthSnapshot::class);
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(NetWorthSnapshot::class)->latestOfMany('recorded_at');
    }

    public function snapshotForPeriod(Period $period): ?NetWorthSnapshot
    {
        return $this->snapshots()
            ->where('period_id', $period->id)
            ->latest('recorded_at')
            ->first()
            ?? $this->snapshots()
                ->whereDate('recorded_at', '<=', $period->end_date)
                ->latest('recorded_at')
                ->first();
    }

    public function periods(): BelongsToMany
    {
        return $this->belongsToMany(Period::class, 'net_worth_account_periods');
    }

    public function netWorthAccountPeriods(): HasMany
    {
        return $this->hasMany(NetWorthAccountPeriod::class);
    }

    public function isArchivedInPeriod(Period $period): bool
    {
        return $this->netWorthAccountPeriods()
            ->where('period_id', $period->id)
            ->where('is_archived', true)
            ->exists();
    }
}
