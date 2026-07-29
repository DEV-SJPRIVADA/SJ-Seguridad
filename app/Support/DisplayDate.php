<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class DisplayDate
{
    /** Formato estándar de fechas en tablas y listados (dd/mm/yy). */
    public const TABLE_DATE = 'd/m/y';

    /** Fecha y hora en tablas (dd/mm/yy HH:mm). */
    public const TABLE_DATETIME = 'd/m/y H:i';

    public static function date(DateTimeInterface|string|null $value, string $empty = '—'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);

        return $date->format(self::TABLE_DATE);
    }

    public static function dateTime(DateTimeInterface|string|null $value, string $empty = '—'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);

        return $date->format(self::TABLE_DATETIME);
    }
}
