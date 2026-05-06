<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetWorthSnapshot extends Model
{
    protected $fillable = [
        'net_worth_account_id',
        'balance',
        'recorded_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function netWorthAccount(): BelongsTo
    {
        return $this->belongsTo(NetWorthAccount::class);
    }
}
