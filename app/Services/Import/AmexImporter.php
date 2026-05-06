<?php

namespace App\Services\Import;

use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Spatie\SimpleExcel\SimpleExcelReader;

class AmexImporter extends BaseImporter
{
    public function import(string $filePath, int $accountId): ImportResult
    {
        $result = new ImportResult;

        try {
            $rows = SimpleExcelReader::create($filePath, 'csv')->getRows();

            $rows->each(function (array $row) use ($accountId, $result) {
                try {
                    $dateStr = $row['Date'] ?? null;
                    if (! $dateStr) {
                        return;
                    }

                    $date = Carbon::parse($dateStr);
                    $description = trim($row['Description'] ?? 'Unknown');

                    // AmEx exports expenses as positive — negate to store as negative
                    $rawAmount = (float) ($row['Amount'] ?? 0);
                    $amount = -$rawAmount;

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
