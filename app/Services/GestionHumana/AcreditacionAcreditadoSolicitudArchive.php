<?php

namespace App\Services\GestionHumana;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Al fijar VIGEN.ACR: archiva la fecha de solicitud previa en observaciones.
 */
final class AcreditacionAcreditadoSolicitudArchive
{
    /**
     * Agrega «Última solicitud: dd/mm/yyyy» en línea nueva si había fecha.
     * Sin fecha previa → no altera observaciones.
     */
    public function appendUltimaSolicitud(
        ?string $observaciones,
        DateTimeInterface|string|null $fechaSolicitud,
    ): ?string {
        if ($fechaSolicitud === null || $fechaSolicitud === '') {
            return $observaciones;
        }

        $formatted = ($fechaSolicitud instanceof DateTimeInterface
            ? Carbon::instance($fechaSolicitud)
            : Carbon::parse($fechaSolicitud)
        )->format('d/m/Y');

        $line = 'Última solicitud: '.$formatted;
        $current = trim((string) ($observaciones ?? ''));
        $merged = $current === '' ? $line : $current."\n".$line;

        $max = (int) config('acreditaciones.limits.observaciones_max', 5000);
        if (mb_strlen($merged) > $max) {
            return mb_substr($merged, 0, $max);
        }

        return $merged;
    }
}
