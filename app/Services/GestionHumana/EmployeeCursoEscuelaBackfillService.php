<?php

namespace App\Services\GestionHumana;

use App\Models\CursoEscuela;
use App\Models\EmployeeCurso;
use Illuminate\Support\Collection;

/**
 * Completa snapshot de escuela en registros de curso desde No.CURSO + catálogo Escuelas.
 */
final class EmployeeCursoEscuelaBackfillService
{
    /**
     * @return array{scanned: int, updated: int, skipped_no_codigo: int, skipped_no_catalog: int, skipped_already_filled: int}
     */
    public function backfill(?int $limit = null): array
    {
        $stats = [
            'scanned' => 0,
            'updated' => 0,
            'skipped_no_codigo' => 0,
            'skipped_no_catalog' => 0,
            'skipped_already_filled' => 0,
        ];

        $query = EmployeeCurso::query()
            ->orderBy('id')
            ->where(function ($q): void {
                $q->whereNull('curso_escuela_id')
                    ->orWhereNull('escuela_nit')
                    ->orWhere('escuela_nit', '')
                    ->orWhereNull('escuela_codigo')
                    ->orWhere('escuela_codigo', '')
                    ->orWhereNull('escuela_nombre')
                    ->orWhere('escuela_nombre', '');
            });

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $query->chunkById(200, function (Collection $rows) use (&$stats): void {
            foreach ($rows as $curso) {
                if (! $curso instanceof EmployeeCurso) {
                    continue;
                }

                $stats['scanned']++;

                $hasCompleteSnapshot = $curso->curso_escuela_id !== null
                    && trim((string) $curso->escuela_nit) !== ''
                    && trim((string) $curso->escuela_codigo) !== '';

                if ($hasCompleteSnapshot) {
                    $stats['skipped_already_filled']++;

                    continue;
                }

                $escuela = CursoEscuela::findActiveByNumeroCurso((string) $curso->numero_curso);
                if ($escuela === null) {
                    $codigo = CursoEscuela::extractCodigoFromNumeroCurso((string) $curso->numero_curso);
                    if ($codigo === null) {
                        $stats['skipped_no_codigo']++;
                    } else {
                        $stats['skipped_no_catalog']++;
                    }

                    continue;
                }

                $curso->forceFill([
                    'curso_escuela_id' => $escuela->id,
                    'escuela_codigo' => $escuela->codigo,
                    'escuela_nit' => $escuela->nit,
                    'escuela_nombre' => $escuela->nombre,
                ])->save();

                $stats['updated']++;
            }
        });

        return $stats;
    }
}
