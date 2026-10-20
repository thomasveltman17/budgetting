<?php

namespace Tests\Unit;

use App\Services\ProviderDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProviderDirectoryTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function names(): array
    {
        return [
            'short Rabobank name' => ['Rabo sparen (noodbuffer)', 'rabobank'],
            'full Rabobank name' => ['RABOBANK SPAARREKENING', 'rabobank'],
            'Revolut pocket' => ['Revolut vrij sparen', 'revolut'],
            'Amex abbreviation' => ['Amex', 'amex'],
            'American Express merchant' => ['AMERICAN EXPRESS EUROPE S.A.', 'amex'],
            'Trading 212 with space' => ['Trading 212 (investment rekening)', 'trading212'],
            'Trading212 without space' => ['Trading212 EU GmbH', 'trading212'],
            'word that only contains rabo' => ['Rabobranding Studio', null],
            'unknown merchant' => ['Albert Heijn', null],
            'empty text' => ['', null],
        ];
    }

    #[DataProvider('names')]
    public function test_it_detects_the_provider_named_in_text(string $text, ?string $expected): void
    {
        $this->assertSame($expected, (new ProviderDirectory)->detect($text));
    }

    public function test_every_provider_has_a_logo_file(): void
    {
        $directory = new ProviderDirectory;

        foreach (['rabobank', 'revolut', 'amex', 'trading212'] as $key) {
            $this->assertTrue($directory->has($key));
            $this->assertFileExists(__DIR__.'/../../public/'.$directory->logo($key));
        }
    }

    public function test_unknown_keys_have_no_logo(): void
    {
        $directory = new ProviderDirectory;

        $this->assertFalse($directory->has('ing'));
        $this->assertFalse($directory->has(null));
        $this->assertNull($directory->logo('ing'));
    }
}
