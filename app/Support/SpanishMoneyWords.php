<?php

namespace App\Support;

/**
 * Convierte montos a texto en español (Colombia) para plantillas Word.
 */
final class SpanishMoneyWords
{
    /**
     * Ej.: 1500000 → "UN MILLÓN QUINIENTOS MIL PESOS"
     */
    public static function pesos(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        if (! is_numeric($amount)) {
            return '';
        }

        $value = (int) round((float) $amount);

        if ($value < 0) {
            return '';
        }

        if ($value === 0) {
            return 'CERO PESOS';
        }

        if (! class_exists(\NumberFormatter::class)) {
            return '';
        }

        $formatter = new \NumberFormatter('es_CO', \NumberFormatter::SPELLOUT);
        $words = $formatter->format($value);

        if ($words === false || trim((string) $words) === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/u', ' ', trim((string) $words)) ?? '';

        return mb_strtoupper($normalized.' pesos', 'UTF-8');
    }
}
