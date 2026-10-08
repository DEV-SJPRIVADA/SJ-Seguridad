<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Festivos nacionales de Colombia (Ley 51 de 1983 / Emiliani + Semana Santa).
 *
 * Sirve para cálculos que cruzan fin de año: genera fechas por año calendario sin tablas BD.
 */
final class ColombiaHolidays
{
    /**
     * ¿La fecha es festivo nacional colombiano?
     */
    public function isHoliday(CarbonInterface|string $date): bool
    {
        $carbon = Carbon::parse($date)->startOfDay();
        $set = $this->datesForYear((int) $carbon->year);

        return isset($set[$carbon->toDateString()]);
    }

    /**
     * Fechas ISO (Y-m-d) de festivos para un rango de años inclusive.
     *
     * @return list<string>
     */
    public function isoDatesForYears(int $fromYear, int $toYear): array
    {
        if ($toYear < $fromYear) {
            [$fromYear, $toYear] = [$toYear, $fromYear];
        }

        $dates = [];
        for ($year = $fromYear; $year <= $toYear; $year++) {
            foreach (array_keys($this->datesForYear($year)) as $iso) {
                $dates[] = $iso;
            }
        }

        sort($dates);

        return $dates;
    }

    /**
     * @return array<string, true> mapa Y-m-d => true
     */
    public function datesForYear(int $year): array
    {
        $dates = [];

        // Fijos.
        foreach ([
            sprintf('%04d-01-01', $year),
            sprintf('%04d-05-01', $year),
            sprintf('%04d-07-20', $year),
            sprintf('%04d-08-07', $year),
            sprintf('%04d-12-08', $year),
            sprintf('%04d-12-25', $year),
        ] as $iso) {
            $dates[$iso] = true;
        }

        // Trasladables al lunes siguiente (Emiliani) si no caen en lunes.
        foreach ([
            sprintf('%04d-01-06', $year), // Reyes
            sprintf('%04d-03-19', $year), // San José
            sprintf('%04d-06-29', $year), // San Pedro y San Pablo
            sprintf('%04d-08-15', $year), // Asunción
            sprintf('%04d-10-12', $year), // Día de la Raza
            sprintf('%04d-11-01', $year), // Todos los Santos
            sprintf('%04d-11-11', $year), // Independencia de Cartagena
        ] as $iso) {
            $dates[$this->nextMondayOnOrAfter($iso)] = true;
        }

        // Semana Santa y festivos móviles desde Pascua.
        $easter = $this->easterSunday($year);
        $dates[$easter->copy()->subDays(3)->toDateString()] = true; // Jueves Santo
        $dates[$easter->copy()->subDays(2)->toDateString()] = true; // Viernes Santo
        $dates[$this->nextMondayOnOrAfter($easter->copy()->addDays(39)->toDateString())] = true; // Ascensión
        $dates[$this->nextMondayOnOrAfter($easter->copy()->addDays(60)->toDateString())] = true; // Corpus Christi
        $dates[$this->nextMondayOnOrAfter($easter->copy()->addDays(68)->toDateString())] = true; // Sagrado Corazón

        ksort($dates);

        return $dates;
    }

    private function nextMondayOnOrAfter(string $isoDate): string
    {
        $date = Carbon::parse($isoDate)->startOfDay();
        while ($date->dayOfWeek !== Carbon::MONDAY) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    /**
     * Domingo de Pascua (algoritmo gregoriano Meeus/Jones/Butcher).
     */
    private function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
