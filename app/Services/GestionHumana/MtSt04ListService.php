<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Query filtrada de la matriz MT-ST-04 con join live a Ficha.
 */
class MtSt04ListService
{
    /**
     * @param  array{
     *     document_number?: string|null,
     *     full_name?: string|null,
     *     q?: string|null,
     *     estado_1?: string|null,
     *     estado_2?: string|null,
     *     arma?: string|null,
     *     apto?: string|null,
     *     ficha_estado?: string|null,
     * }  $filters
     * @return Builder<MtSt04Registro>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = MtSt04Registro::query()
            ->leftJoin(
                'employee_ficha_profiles as ficha',
                'ficha.document_number',
                '=',
                'mt_st_04_registros.document_number',
            )
            ->select([
                'mt_st_04_registros.*',
                'ficha.full_name as ficha_full_name',
                'ficha.position_name as ficha_position_name',
                'ficha.work_city_name as ficha_work_city_name',
                'ficha.cost_center_name as ficha_cost_center_name',
                'ficha.employment_status as ficha_employment_status',
            ]);

        $this->applyFichaEstadoFilter($query, $filters);

        $cedula = trim((string) ($filters['document_number'] ?? ''));
        if ($cedula !== '') {
            $query->where('mt_st_04_registros.document_number', 'like', '%'.$cedula.'%');
        }

        $name = trim((string) ($filters['full_name'] ?? ''));
        if ($name !== '') {
            $query->where('ficha.full_name', 'like', '%'.$name.'%');
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('mt_st_04_registros.document_number', 'like', $like)
                    ->orWhere('ficha.full_name', 'like', $like);
            });
        }

        $estado1 = (string) ($filters['estado_1'] ?? '');
        if ($estado1 !== '' && $estado1 !== 'todos') {
            if ($estado1 === '__empty__') {
                $query->whereNull('mt_st_04_registros.estado_1');
            } else {
                $query->where('mt_st_04_registros.estado_1', $estado1);
            }
        }

        $estado2 = (string) ($filters['estado_2'] ?? '');
        if ($estado2 !== '' && $estado2 !== 'todos') {
            if ($estado2 === '__empty__') {
                $query->whereNull('mt_st_04_registros.estado_2');
            } else {
                $query->where('mt_st_04_registros.estado_2', $estado2);
            }
        }

        $arma = (string) ($filters['arma'] ?? '');
        if ($arma !== '' && $arma !== 'todos') {
            $query->where('mt_st_04_registros.arma', $arma);
        }

        $apto = (string) ($filters['apto'] ?? '');
        if ($apto !== '' && $apto !== 'todos') {
            $query->where('mt_st_04_registros.apto', $apto);
        }

        if ($ordered) {
            $query->orderByDesc('mt_st_04_registros.id');
        }

        return $query;
    }

    /**
     * @param  Builder<MtSt04Registro>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFichaEstadoFilter(Builder $query, array $filters): void
    {
        $fichaEstado = (string) ($filters['ficha_estado'] ?? EmployeeFichaProfile::STATUS_ACTIVO);

        if ($fichaEstado === '' || $fichaEstado === 'todos') {
            return;
        }

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        $query->where('ficha.employment_status', $fichaEstado);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, MtSt04Registro>
     */
    public function all(array $filters): Collection
    {
        return $this->filteredQuery($filters)->get();
    }
}
