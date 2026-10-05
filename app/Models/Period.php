<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * Number of calendar days in the period, both ends included.
     */
    public function lengthInDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Number of days of the period that have begun on the given date: 0 before
     * the period starts, the full length once it has ended.
     */
    public function elapsedDays(?CarbonInterface $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();

        if ($today->lt($this->start_date)) {
            return 0;
        }

        return min($this->lengthInDays(), (int) $this->start_date->diffInDays($today) + 1);
    }
}
