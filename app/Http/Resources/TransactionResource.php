<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use App\Services\PeriodService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'account' => $this->account->label,
            'category' => $this->category?->name,
            'period' => app(PeriodService::class)->formatLabel($this->period),
            'notes' => $this->notes,
        ];
    }

    /**
     * A ready-made confirmation line for a Shortcuts notification.
     *
     * @return array<string, string>
     */
    public function with(Request $request): array
    {
        $formattedAmount = ($this->amount < 0 ? '-' : '+').'€'.number_format(abs((float) $this->amount), 2, ',', '.');

        return [
            'message' => collect([$formattedAmount, $this->description, $this->account->label, $this->category?->name ?? 'Uncategorized'])->implode(' · '),
        ];
    }
}
