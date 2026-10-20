<?php

namespace App\Services;

/**
 * The banks and brokers that show up in the app, with the logo for each and
 * the words that identify them in free text such as account or merchant names.
 */
class ProviderDirectory
{
    /**
     * @var array<string, array{label: string, logo: string, pattern: string}>
     */
    private const PROVIDERS = [
        'rabobank' => [
            'label' => 'Rabobank',
            'logo' => 'images/providers/rabobank.png',
            'pattern' => '/\brabo(bank)?\b/i',
        ],
        'revolut' => [
            'label' => 'Revolut',
            'logo' => 'images/providers/revolut.png',
            'pattern' => '/\brevolut\b/i',
        ],
        'amex' => [
            'label' => 'American Express',
            'logo' => 'images/providers/amex.png',
            'pattern' => '/\b(amex|american express)\b/i',
        ],
        'trading212' => [
            'label' => 'Trading 212',
            'logo' => 'images/providers/trading212.png',
            'pattern' => '/\b(trading ?212|t212)\b/i',
        ],
    ];

    /**
     * Find the provider mentioned in a piece of text, e.g. "Rabo sparen" or
     * "Trading 212 EU GmbH".
     */
    public function detect(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        foreach (self::PROVIDERS as $key => $provider) {
            if (preg_match($provider['pattern'], $text) === 1) {
                return $key;
            }
        }

        return null;
    }

    public function has(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::PROVIDERS);
    }

    public function label(string $key): string
    {
        return self::PROVIDERS[$key]['label'] ?? $key;
    }

    /**
     * Public path of the provider's logo, relative to the web root.
     */
    public function logo(string $key): ?string
    {
        return self::PROVIDERS[$key]['logo'] ?? null;
    }
}
