<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetWorthAccountPeriod extends Model
{
    protected $fillable = [
        'net_worth_account_id',
        'period_id',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function netWorthAccount(): BelongsTo
    {
        return $this->belongsTo(NetWorthAccount::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
