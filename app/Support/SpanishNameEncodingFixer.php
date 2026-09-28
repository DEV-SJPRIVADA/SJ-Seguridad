<?php

namespace App\Support;

/**
 * Repara texto de nombres con encoding roto (CP1252→UTF-8) o `?` donde iba Ñ/Ó.
 *
 * Origen típico: extractos / Excel exportados con charset incorrecto que ya sustituyeron
 * la letra por `?` (p. ej. MU?OZ → MUÑOZ, LE?N → LEÓN).
 */
class SpanishNameEncodingFixer
{
    public static function normalizeEncoding(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }

            $converted = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');

            return is_string($converted) ? $converted : $value;
        }

        return $value;
    }

    /**
     * Normaliza encoding y repara `?` perdidos en nombres de persona.
     */
    public static function fixPersonName(string $value): string
    {
        $value = trim(self::normalizeEncoding($value));

        if ($value === '' || ! str_contains($value, '?')) {
            return $value;
        }

        return self::repairLostSpanishLetters($value);
    }

    public static function fixPersonNameOrNull(mixed $value): ?string
    {
        $fixed = self::fixPersonName(trim((string) ($value ?? '')));

        return $fixed === '' ? null : $fixed;
    }

    private static function repairLostSpanishLetters(string $value): string
    {
        // LE?N es LEÓN (ó), no LEÑN.
        $value = preg_replace_callback('/\b(L)(E)\?(N)\b/iu', static function (array $m): string {
            $o = $m[2] === 'E' ? 'Ó' : 'ó';

            return $m[1].$m[2].$o.$m[3];
        }, $value) ?? $value;

        // `?` pegado a letras (MU?OZ, ?A?EZ, CA?ON) → Ñ / ñ según el vecino.
        return preg_replace_callback(
            '/(\p{L})?\?(\p{L})?/u',
            static function (array $m): string {
                $before = $m[1] ?? '';
                $after = $m[2] ?? '';

                if ($before === '' && $after === '') {
                    return '?';
                }

                $neighbor = $before !== '' ? $before : $after;
                $enye = mb_strtolower($neighbor) === $neighbor ? 'ñ' : 'Ñ';

                return $before.$enye.$after;
            },
            $value
        ) ?? $value;
    }
}
