<?php

namespace Tests\Unit;

use App\Models\Transaction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TransactionMerchantNameTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function descriptions(): array
    {
        return [
            'plain merchant' => ['Albert Heijn', 'Albert Heijn'],
            'Revolut outgoing transfer' => ['To Kruidvat', 'Kruidvat'],
            'Revolut incoming transfer' => ['From Sparen', 'Sparen'],
            'Rabobank card payment' => ['Slagerij LeidscheRijn — UTRECHT, 3541CX, NLD, 12:22', 'Slagerij LeidscheRijn'],
            'payment processor prefix' => ['BCK*AH to go 5865 Utre — UTRECHT, 3511CE, NLD, 19:41', 'AH to go 5865 Utre'],
            'location suffix after asterisk' => ['Booking.com*AMSTERDAM', 'Booking.com'],
            'paid via another party' => ['To KLARNA BANK AB via Stichting Mollie Payments', 'KLARNA BANK AB'],
            'Dutch surname is kept' => ['Van der Berg Fietsen', 'Van der Berg Fietsen'],
        ];
    }

    #[DataProvider('descriptions')]
    public function test_merchant_name_is_cleaned_from_the_bank_description(string $description, string $expected): void
    {
        $transaction = new Transaction(['description' => $description]);

        $this->assertSame($expected, $transaction->merchant_name);
    }
}
