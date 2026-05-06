<?php

namespace App\Services\Import;

class ImportResult
{
    public int $imported = 0;

    public int $skippedDuplicates = 0;

    /** @var string[] */
    public array $errors = [];

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }
}
