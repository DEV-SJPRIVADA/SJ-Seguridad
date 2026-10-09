<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Calcula vencimientos (+364) y estados ESTADO / ESTADO2 (TZ America/Bogota).
 */
class MtSt04EstadoCalculator
{
    public function timezone(): string
    {
        return (string) config('mt_st_04.timezone', 'America/Bogota');
    }

    public function vencimientoDias(): int
    {
        return (int) config('mt_st_04.vencimiento_dias', 364);
    }

    public function venceraDias(): int
    {
        return (int) config('mt_st_04.vencera_dias', 30);
    }

    /**
     * @return list<string>
     */
    public function noAplicaCargos(): array
    {
        /** @var list<string> $cargos */
        $cargos = array_values(config('mt_st_04.no_aplica_cargos', ['GUARDA', 'OPERADOR']));

        return $cargos;
    }

    /**
     * Vencimiento = fecha examen + N días; null si no hay examen.
     */
    public function calculateVencimiento(?CarbonInterface $fechaExamen): ?Carbon
    {
        if ($fechaExamen === null) {
            return null;
        }

        return $fechaExamen->copy()->startOfDay()->addDays($this->vencimientoDias());
    }

    /**
     * Vigencia genérica: null / VENCIDO / VENCERA / VIGENTE.
     */
    public function calculateEstadoVigencia(
        ?CarbonInterface $fechaVencimiento,
        ?CarbonInterface $today = null,
    ): ?string {
        if ($fechaVencimiento === null) {
            return null;
        }

        $hoy = ($today ?? Carbon::today($this->timezone()))->copy()->startOfDay();
        $vencimiento = $fechaVencimiento->copy()->startOfDay();

        if ($vencimiento->lt($hoy)) {
            return MtSt04Registro::ESTADO_VENCIDO;
        }

        $umbralVencera = $hoy->copy()->addDays($this->venceraDias());

        if ($vencimiento->lte($umbralVencera)) {
            return MtSt04Registro::ESTADO_VENCERA;
        }

        return MtSt04Registro::ESTADO_VIGENTE;
    }

    /**
     * ESTADO2: NO APLICA si cargo exacto GUARDA|OPERADOR; si no, vigencia sobre vencimiento 2.
     */
    public function calculateEstado2(
        ?string $cargo,
        ?CarbonInterface $fechaVencimiento,
        ?CarbonInterface $today = null,
    ): ?string {
        if ($this->isNoAplicaCargo($cargo)) {
            return MtSt04Registro::ESTADO_NO_APLICA;
        }

        return $this->calculateEstadoVigencia($fechaVencimiento, $today);
    }

    public function isNoAplicaCargo(?string $cargo): bool
    {
        $normalized = mb_strtoupper(trim((string) $cargo), 'UTF-8');

        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, $this->noAplicaCargos(), true);
    }

    /**
     * Recalcula vencimientos y estados en memoria (no guarda).
     *
     * @return bool true si algún campo de vencimiento/estado cambió
     */
    public function applyToModel(
        MtSt04Registro $registro,
        ?string $cargo,
        ?CarbonInterface $today = null,
    ): bool {
        $today ??= Carbon::today($this->timezone());

        $venc1 = $this->calculateVencimiento($registro->fecha_examen_1);
        $venc2 = $this->calculateVencimiento($registro->fecha_examen_2);
        $estado1 = $this->calculateEstadoVigencia($venc1, $today);
        $estado2 = $this->calculateEstado2($cargo, $venc2, $today);

        $changed = false;

        if ($this->dateChanged($registro->fecha_vencimiento_1, $venc1)) {
            $registro->fecha_vencimiento_1 = $venc1;
            $changed = true;
        }

        if ($this->dateChanged($registro->fecha_vencimiento_2, $venc2)) {
            $registro->fecha_vencimiento_2 = $venc2;
            $changed = true;
        }

        if ($registro->estado_1 !== $estado1) {
            $registro->estado_1 = $estado1;
            $changed = true;
        }

        if ($registro->estado_2 !== $estado2) {
            $registro->estado_2 = $estado2;
            $changed = true;
        }

        return $changed;
    }

    /**
     * Recorre todos los registros, lee CARGO live de Ficha y recalcula estados.
     *
     * @return array{scanned: int, updated: int}
     */
    public function syncAll(?CarbonInterface $today = null): array
    {
        $today ??= Carbon::today($this->timezone());
        $scanned = 0;
        $updated = 0;

        MtSt04Registro::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($today, &$scanned, &$updated): void {
                $documentNumbers = $rows
                    ->pluck('document_number')
                    ->map(fn (mixed $doc): string => (string) $doc)
                    ->filter(fn (string $doc): bool => $doc !== '')
                    ->unique()
                    ->values()
                    ->all();

                $cargos = EmployeeFichaProfile::query()
                    ->whereIn('document_number', $documentNumbers)
                    ->pluck('position_name', 'document_number');

                /** @var MtSt04Registro $registro */
                foreach ($rows as $registro) {
                    $scanned++;
                    $cargo = trim((string) ($cargos[$registro->document_number] ?? ''));

                    if ($this->applyToModel($registro, $cargo, $today)) {
                        $registro->save();
                        $updated++;
                    }
                }
            });

        return [
            'scanned' => $scanned,
            'updated' => $updated,
        ];
    }

    private function dateChanged(?CarbonInterface $current, ?CarbonInterface $next): bool
    {
        if ($current === null && $next === null) {
            return false;
        }

        if ($current === null || $next === null) {
            return true;
        }

        return $current->toDateString() !== $next->toDateString();
    }
}
