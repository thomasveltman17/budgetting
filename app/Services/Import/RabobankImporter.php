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
                ->getRows();

            $rows->each(function (array $row) use ($accountId, $result) {
                try {
                    $dateStr = $row['Datum'] ?? null;
                    if (! $dateStr) {
                        return;
                    }

                    $date = Carbon::createFromFormat('Y-m-d', $dateStr);

                    $counterparty = trim($row['Naam tegenpartij'] ?? '');
                    $memo = trim($row['Omschrijving-1'] ?? '');
                    $description = $counterparty !== '' && $memo !== ''
                        ? $counterparty.' — '.$memo
                        : ($counterparty ?: ($memo ?: 'Unknown'));
                    $description = substr(trim($description), 0, 255);

                    // Bedrag already carries the sign: '-10,00' or '+102,58'
                    // Dutch notation: comma = decimal separator, dot = thousands separator
                    $rawAmount = $row['Bedrag'] ?? '0';
                    $amount = (float) str_replace(',', '.', str_replace('.', '', $rawAmount));

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
