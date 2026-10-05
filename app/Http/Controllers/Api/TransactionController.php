<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PeriodService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request, PeriodService $periodService): JsonResponse
    {
        $date = $request->transactionDate();

        $transaction = Transaction::create([
            'period_id' => $periodService->findOrCreateForDate($date)->id,
            'account_id' => $request->account()->id,
            'category_id' => $request->category()?->id,
            'date' => $date->toDateString(),
            'description' => $request->validated('description'),
            'amount' => $request->signedAmount(),
            'source' => 'shortcut',
            'notes' => $request->validated('notes'),
        ]);

        return TransactionResource::make($transaction->load(['account', 'category', 'period']))
            ->response()
            ->setStatusCode(201);
    }
}
