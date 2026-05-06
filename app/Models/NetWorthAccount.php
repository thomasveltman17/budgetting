<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
}
