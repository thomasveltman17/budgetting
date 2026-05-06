<?php

namespace App\Services\Import;

use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Spatie\SimpleExcel\SimpleExcelReader;

class RabobankImporter extends BaseImporter
{
    public function import(string $filePath, int $accountId): ImportResult
    {
        $result = new ImportResult;

        try {
            $rows = SimpleExcelReader::create($filePath, 'csv')
                ->useDelimiter(';')
                ->getRows();

            $rows->each(function (array $row) use ($accountId, $result) {
                try {
                    $dateStr = $row['Datum'] ?? null;
                    if (! $dateStr) {
                        return;
                    }

                    $date = Carbon::createFromFormat('Y-m-d', $dateStr);

                    $description = trim(
                        ($row['Naam tegenpartij'] ?? '').' '.($row['Omschrijving-1'] ?? '')
                    );
                    $description = $description ?: ($row['Omschrijving-2'] ?? 'Unknown');
                    $description = trim($description);

                    $rawAmount = $row['Bedrag'] ?? '0';
                    $amount = (float) str_replace(',', '.', str_replace('.', '', $rawAmount));

                    $debitCredit = strtoupper($row['Af Bij'] ?? 'D');
                    if ($debitCredit === 'D') {
                        $amount = -abs($amount);
                    } else {
                        $amount = abs($amount);
                    }

                    $hash = $this->generateHash($accountId, $date->toDateString(), (string) $amount, $description);

                    if ($this->isDuplicate($hash)) {
                        $result->skippedDuplicates++;

                        return;
                    }

                    $period = $this->findOrCreatePeriod($date);

                    Transaction::create([
                        'period_id' => $period->id,
                        'account_id' => $accountId,
                        'category_id' => null,
                        'date' => $date->toDateString(),
                        'description' => $description,
                        'amount' => $amount,
                        'source' => 'import',
                        'import_hash' => $hash,
                    ]);

                    $result->imported++;
                } catch (Exception $e) {
                    $result->addError('Row error: '.$e->getMessage());
                }
            });
        } catch (Exception $e) {
            $result->addError('File error: '.$e->getMessage());
        }

        return $result;
    }
}
