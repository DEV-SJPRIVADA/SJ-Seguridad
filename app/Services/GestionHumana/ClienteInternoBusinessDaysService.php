<?php

namespace App\Services\GestionHumana;

use Carbon\CarbonInterface;

/**
 * Días hábiles lun–vie entre fecha_solicitud y fecha_respuesta (V1 sin festivos).
 *
 * Regla: cuenta días estrictamente posteriores a fecha_solicitud hasta e incluida fecha_respuesta.
 * Ej.: lun → vie = 4; mismo día = 0; sin fecha_respuesta = null.
 */
final class ClienteInternoBusinessDaysService
{
    public function count(?CarbonInterface $fechaSolicitud, ?CarbonInterface $fechaRespuesta): ?int
    {
        if ($fechaSolicitud === null || $fechaRespuesta === null) {
            return null;
        }

        $start = $fechaSolicitud->copy()->startOfDay();
        $end = $fechaRespuesta->copy()->startOfDay();

        if ($end->lt($start)) {
            return null;
        }

        $count = 0;
        $cursor = $start->copy()->addDay();

        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    /**
     * Resuelve dias_respuesta + flag manual según algoritmo del brief FEAT-042.
     *
     * @return array{0: int|null, 1: bool}
     */
    public function resolveForCreate(
        ?CarbonInterface $fechaSolicitud,
        ?CarbonInterface $fechaRespuesta,
        ?int $requestDias,
        bool $requestDiasProvided,
    ): array {
        $calculated = $this->count($fechaSolicitud, $fechaRespuesta);

        if ($requestDiasProvided && ($fechaRespuesta === null || $requestDias !== $calculated)) {
            return [$requestDias, true];
        }

        return [$calculated, false];
    }

    /**
     * @return array{0: int|null, 1: bool}
     */
    public function resolveForUpdate(
        ?CarbonInterface $fechaSolicitud,
        ?CarbonInterface $fechaRespuesta,
        ?int $requestDias,
        bool $requestDiasProvided,
        bool $diasTouched,
        bool $existingManual,
        ?int $existingDias,
        bool $clearOverride = false,
    ): array {
        if ($clearOverride) {
            return [$this->count($fechaSolicitud, $fechaRespuesta), false];
        }

        // Solo aplicar override si el usuario editó el input de días.
        if ($diasTouched) {
            if ($requestDiasProvided) {
                $calculated = $this->count($fechaSolicitud, $fechaRespuesta);

                if ($fechaRespuesta === null || $requestDias !== $calculated) {
                    return [$requestDias, true];
                }

                return [$calculated, false];
            }

            return [$this->count($fechaSolicitud, $fechaRespuesta), false];
        }

        if ($existingManual) {
            return [$existingDias, true];
        }

        return [$this->count($fechaSolicitud, $fechaRespuesta), false];
    }
}
