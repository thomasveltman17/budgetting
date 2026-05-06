<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'type',
        'color',
        'is_archived',
        'sort_order',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgetTargets(): HasMany
    {
        return $this->hasMany(BudgetTarget::class);
    }
}
