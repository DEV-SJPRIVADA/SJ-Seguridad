<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AcreditacionEstadoCalculator
{
    /**
     * Prioridad (brief FEAT-036):
     * 1) Si hay FECHA SOLICITUD → EN_PROCESO
     * 2) Si no hay VIGEN.ACR (vacío / trámite) → EN_PROCESO
     * 3) Si VIGEN.ACR ≤ hoy → DESACREDITADO
     * 4) Si VIGEN.ACR ≤ hoy + N días → POR_VENCER
     * 5) Else → ACREDITADO
     */
    public function calculate(
        ?CarbonInterface $fechaSolicitud,
        ?CarbonInterface $vigenciaAcr,
        ?CarbonInterface $today = null,
    ): string {
        $today ??= Carbon::today(config('app.timezone', 'America/Bogota'));

        if ($fechaSolicitud !== null) {
            return AcreditacionAcreditado::ESTADO_EN_PROCESO;
        }

        if ($vigenciaAcr === null) {
            // Sin vigencia (p. ej. import con celda vacía o «en proceso») → trámite en curso.
            return AcreditacionAcreditado::ESTADO_EN_PROCESO;
        }

        $vigencia = $vigenciaAcr->copy()->startOfDay();
        $hoy = $today->copy()->startOfDay();

        if ($vigencia->lte($hoy)) {
            return AcreditacionAcreditado::ESTADO_DESACREDITADO;
        }

        $porVencerDays = (int) config('acreditaciones.por_vencer_days', 21);
        $umbralPorVencer = $hoy->copy()->addDays($porVencerDays);

        if ($vigencia->lte($umbralPorVencer)) {
            return AcreditacionAcreditado::ESTADO_POR_VENCER;
        }

        return AcreditacionAcreditado::ESTADO_ACREDITADO;
    }

    public function calculateForModel(AcreditacionAcreditado $acreditado, ?CarbonInterface $today = null): string
    {
        return $this->calculate(
            $acreditado->fecha_solicitud,
            $acreditado->vigencia_acr,
            $today,
        );
    }

    public function applyToModel(AcreditacionAcreditado $acreditado, ?CarbonInterface $today = null): bool
    {
        $next = $this->calculateForModel($acreditado, $today);

        if ($next === $acreditado->estado) {
            return false;
        }

        $acreditado->estado = $next;

        return true;
    }

    /**
     * Recorre todos los acreditados y recalcula estado.
     *
     * @return array{scanned: int, updated: int}
     */
    public function syncAll(?CarbonInterface $today = null): array
    {
        $today ??= Carbon::today(config('app.timezone', 'America/Bogota'));
        $scanned = 0;
        $updated = 0;

        AcreditacionAcreditado::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($today, &$scanned, &$updated): void {
                /** @var AcreditacionAcreditado $acreditado */
                foreach ($rows as $acreditado) {
                    $scanned++;

                    if ($this->applyToModel($acreditado, $today)) {
                        $acreditado->save();
                        $updated++;
                    }
                }
            });

        return [
            'scanned' => $scanned,
            'updated' => $updated,
        ];
    }

    public function estadoLabel(string $code): string
    {
        /** @var array<string, string> $labels */
        $labels = config('acreditaciones.estados', []);

        return $labels[$code] ?? $code;
    }
}
