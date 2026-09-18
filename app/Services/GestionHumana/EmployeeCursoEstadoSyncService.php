<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class EmployeeCursoEstadoSyncService
{
    /**
     * Estado derivado de la vigencia (sin respetar SOLICITADO).
     */
    public function resolveEstadoFromVigencia(EmployeeCurso $curso, ?CarbonInterface $today = null): string
    {
        if ($curso->computeVigencia($today) === EmployeeCurso::VIGENCIA_VIGENTE) {
            return EmployeeCurso::ESTADO_ACTUALIZADO;
        }

        return EmployeeCurso::ESTADO_PENDIENTE;
    }

    /**
     * Aplica reglas de estado según vigencia.
     *
     * - VIGENTE → ACTUALIZADO (si $forceVigente o el estado no es SOLICITADO)
     * - ACTUALIZAR / VENCIDO → PENDIENTE si el estado no es SOLICITADO
     * - SOLICITADO se conserva salvo $forceVigente en cursos VIGENTE
     *
     * @return bool true si el estado en memoria cambió
     */
    public function applyToModel(
        EmployeeCurso $curso,
        bool $forceVigente = false,
        bool $preserveSolicitado = true,
        ?CarbonInterface $today = null,
    ): bool {
        $vigencia = $curso->computeVigencia($today);
        $current = $curso->estado;
        $next = $current;

        if ($vigencia === EmployeeCurso::VIGENCIA_VIGENTE) {
            if ($forceVigente || ! $preserveSolicitado || $current !== EmployeeCurso::ESTADO_SOLICITADO) {
                $next = EmployeeCurso::ESTADO_ACTUALIZADO;
            }
        } elseif (! $preserveSolicitado || $current !== EmployeeCurso::ESTADO_SOLICITADO) {
            $next = EmployeeCurso::ESTADO_PENDIENTE;
        }

        if ($next === $current) {
            return false;
        }

        $curso->estado = $next;

        return true;
    }

    /**
     * Sincroniza y guarda si hubo cambio.
     *
     * @return bool true si se persistió un cambio
     */
    public function syncAndSave(
        EmployeeCurso $curso,
        bool $forceVigente = false,
        bool $preserveSolicitado = true,
        ?CarbonInterface $today = null,
    ): bool {
        if (! $this->applyToModel($curso, $forceVigente, $preserveSolicitado, $today)) {
            return false;
        }

        $curso->save();

        return true;
    }

    /**
     * Recorre todos los cursos y aplica sync.
     *
     * @return array{scanned: int, updated: int}
     */
    public function syncAll(bool $backfill = false, ?CarbonInterface $today = null): array
    {
        $today ??= Carbon::today();
        $scanned = 0;
        $updated = 0;

        EmployeeCurso::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($backfill, $today, &$scanned, &$updated): void {
                /** @var EmployeeCurso $curso */
                foreach ($rows as $curso) {
                    $scanned++;

                    if ($this->syncAndSave(
                        $curso,
                        forceVigente: $backfill,
                        preserveSolicitado: true,
                        today: $today,
                    )) {
                        $updated++;
                    }
                }
            });

        return [
            'scanned' => $scanned,
            'updated' => $updated,
        ];
    }
}
