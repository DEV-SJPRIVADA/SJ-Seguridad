<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;

class AcreditacionValidacionesGateService
{
    /**
     * @return array{
     *     ok: bool,
     *     has_carga: bool,
     *     has_proceso: bool,
     *     has_acreditado: bool,
     *     message: string,
     *     missing: list<string>
     * }
     */
    public function evaluate(string $fechaReporte): array
    {
        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', $fechaReporte)
            ->first();

        if ($carga === null) {
            return [
                'ok' => false,
                'has_carga' => false,
                'has_proceso' => false,
                'has_acreditado' => false,
                'message' => 'No hay carga de Reporte Diario para esta fecha. Cargue ambos orígenes APO antes de validar.',
                'missing' => ['carga'],
            ];
        }

        $hasProceso = $carga->origenHasPriorData(AcreditacionReporteDiarioFila::ORIGEN_PROCESO);
        $hasAcreditado = $carga->origenHasPriorData(AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO);

        if ($hasProceso && $hasAcreditado) {
            return [
                'ok' => true,
                'has_carga' => true,
                'has_proceso' => true,
                'has_acreditado' => true,
                'message' => 'Gate OK: ambos orígenes APO están cargados para esta fecha.',
                'missing' => [],
            ];
        }

        $missing = [];
        $parts = [];

        if (! $hasProceso) {
            $missing[] = AcreditacionReporteDiarioFila::ORIGEN_PROCESO;
            $parts[] = 'Falta carga En proceso';
        }

        if (! $hasAcreditado) {
            $missing[] = AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO;
            $parts[] = 'Falta carga Acreditado APO';
        }

        return [
            'ok' => false,
            'has_carga' => true,
            'has_proceso' => $hasProceso,
            'has_acreditado' => $hasAcreditado,
            'message' => implode('. ', $parts).'. Cargue el origen faltante en Reporte Diario.',
            'missing' => $missing,
        ];
    }
}
