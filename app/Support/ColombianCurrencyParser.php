<?php

namespace App\Support;

class ColombianCurrencyParser
{
    /**
     * Parse Colombian-formatted currency or plain numeric salary into a float.
     */
    public static function parse(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', $raw) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
