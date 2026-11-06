<?php

namespace App\Services\Calidad;

use Carbon\CarbonInterface;

/**
 * Días hábiles lunes a viernes, sin festivos.
 *
 * El conteo entre recepción y verificación incluye ambos extremos si son hábiles
 * (equivalente a DIAS.LAB.INTL de Excel, fin de semana sáb-dom).
 * El plazo suma N días hábiles posteriores a la recepción: ese día no cuenta.
 */
final class ServicioClienteBusinessDaysService
{
    public function countInclusive(?CarbonInterface $start, ?CarbonInterface $end): ?int
    {
        if ($start === null || $end === null) {
            return null;
        }

        $from = $start->copy()->startOfDay();
        $to = $end->copy()->startOfDay();

        if ($to->lt($from)) {
            return null;
        }

        $count = 0;
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            if ($cursor->isWeekday()) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    public function addBusinessDays(CarbonInterface $start, int $days): CarbonInterface
    {
        $cursor = $start->copy()->startOfDay();
        $added = 0;
        $days = max(0, $days);

        while ($added < $days) {
            $cursor->addDay();
            if ($cursor->isWeekday()) {
                $added++;
            }
        }

        return $cursor;
    }
}
