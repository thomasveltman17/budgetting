<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'period_id',
        'parent_transaction_id',
        'account_id',
        'category_id',
        'date',
        'description',
        'amount',
        'source',
        'import_hash',
        'notes',
        'is_pending_return',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_pending_return' => 'boolean',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }
}
