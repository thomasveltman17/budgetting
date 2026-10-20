<?php

namespace App\Services\Ai;

/**
 * Formats block values the way the rest of the app shows them.
 */
class ValueFormatter
{
    public static function format(float|int|string|null $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($format === 'text' || ! is_numeric($value)) {
            return (string) $value;
        }

        $value = (float) $value;
        $sign = $value < 0 ? '−' : '';

        return match ($format) {
            'money' => $sign.'€ '.number_format(abs($value), 2, ',', '.'),
            'percent' => $sign.self::number(abs($value)).'%',
            default => $sign.self::number(abs($value)),
        };
    }

    private static function number(float $value): string
    {
        $decimals = floor($value) == $value ? 0 : (abs($value) < 10 ? 2 : 1);

        return number_format($value, $decimals, ',', '.');
    }
}
