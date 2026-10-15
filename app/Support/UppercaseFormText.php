<?php

namespace App\Support;

/**
 * Normaliza textos de formularios a mayúsculas (UTF-8) al persistir.
 * Excluye correos, claves técnicas y códigos de catálogo/estado.
 */
final class UppercaseFormText
{
    /**
     * Claves (nombre de hoja) que no se pasan a mayúsculas.
     *
     * @var list<string>
     */
    public const DEFAULT_EXCEPT = [
        'email',
        'password',
        'password_confirmation',
        '_token',
        '_method',
        'status',
        'sex',
        'operating_area_key',
        'requesting_area_key',
        'document_type',
        'account_type',
        'linkage_type',
        'risk_level',
        'workday',
        'withholding_type',
        'expense_type',
        'contributor_type',
        'exclude_transport_allowance',
        'confirm_duplicate_hired',
        'confirm_duplicate_hired_document',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $except
     * @return array<string, mixed>
     */
    public static function apply(array $data, array $except = []): array
    {
        $exceptLookup = [];
        foreach ([...self::DEFAULT_EXCEPT, ...$except] as $key) {
            $exceptLookup[mb_strtolower((string) $key)] = true;
        }

        return self::transform($data, $exceptLookup);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, true>  $exceptLookup
     * @return array<string, mixed>
     */
    private static function transform(array $data, array $exceptLookup): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $keyString = (string) $key;
            $keyLower = mb_strtolower($keyString);

            if (isset($exceptLookup[$keyLower]) || str_ends_with($keyLower, '_code') || str_contains($keyLower, 'email')) {
                $out[$key] = $value;

                continue;
            }

            if (is_array($value)) {
                $out[$key] = self::transform($value, $exceptLookup);

                continue;
            }

            if (! is_string($value)) {
                $out[$key] = $value;

                continue;
            }

            $out[$key] = self::uppercaseString($value);
        }

        return $out;
    }

    public static function uppercaseString(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        // Fechas ISO / numéricas: no cambian al upper, se dejan igual.
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return $value;
        }

        return mb_strtoupper($value, 'UTF-8');
    }
}
