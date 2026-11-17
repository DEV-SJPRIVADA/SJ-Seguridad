<?php

namespace App\Support;

/**
 * Presentación de importes en pesos colombianos para pantallas y documentos.
 *
 * Un único punto de formato evita que unas vistas muestren `1,250.00` y otras
 * `$ 1.250,00`. Los cálculos siguen usando el valor numérico original.
 */
final class ColombianCurrency
{
    /** Separador decimal de Colombia (coma) y de miles (punto). */
    public const DECIMAL_SEPARATOR = ',';

    public const THOUSANDS_SEPARATOR = '.';

    /** Importe con símbolo y dos decimales: `$ 1.250,00`. */
    public static function format(mixed $value, int $decimals = 2, string $empty = '—'): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $empty;
        }

        return '$ '.number_format((float) $value, $decimals, self::DECIMAL_SEPARATOR, self::THOUSANDS_SEPARATOR);
    }
}
