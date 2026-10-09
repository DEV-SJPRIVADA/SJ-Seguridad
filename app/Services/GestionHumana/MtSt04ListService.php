<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     *     ciudad?: string|null,
     *     cargo?: string|null,
     *     puesto?: string|null,
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
                'ficha.id as ficha_profile_id',
                'ficha.full_name as ficha_full_name',
                'ficha.position_name as ficha_position_name',
                // CIUDAD: work_city si existe; si no, residence_city (casi toda la Ficha real).
                DB::raw(EmployeeFichaProfile::displayCitySql('ficha').' as ficha_work_city_name'),
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
            } elseif ($estado2 === '__sin_no_aplica__') {
                // Misma regla que el Total del dashboard psicosensométrico.
                $query->where(function (Builder $inner): void {
                    $inner->whereNull('mt_st_04_registros.estado_2')
                        ->orWhere('mt_st_04_registros.estado_2', '!=', MtSt04Registro::ESTADO_NO_APLICA);
                });
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

        // Misma ciudad efectiva que la columna CIUDAD (trabajo o, si vacío, residencia).
        $ciudad = trim((string) ($filters['ciudad'] ?? ''));
        if ($ciudad !== '' && $ciudad !== 'todos') {
            $query->whereRaw(EmployeeFichaProfile::displayCitySql('ficha').' = ?', [$ciudad]);
        }

        $cargo = trim((string) ($filters['cargo'] ?? ''));
        if ($cargo !== '' && $cargo !== 'todos') {
            $query->where('ficha.position_name', $cargo);
        }

        $puesto = trim((string) ($filters['puesto'] ?? ''));
        if ($puesto !== '' && $puesto !== 'todos') {
            $query->where('ficha.cost_center_name', $puesto);
        }

        if ($ordered) {
            $query->orderByDesc('mt_st_04_registros.id');
        }

        return $query;
    }

    /**
     * Opciones de filtro ciudad (ciudades efectivas presentes en la matriz).
     *
     * @return list<array{value: string, label: string}>
     */
    public function cityFilterOptions(): array
    {
        return $this->distinctExpressionOptions(
            EmployeeFichaProfile::displayCitySql('ficha'),
            'Todas',
        );
    }

    /**
     * Opciones de filtro cargo (position_name en Ficha).
     *
     * @return list<array{value: string, label: string}>
     */
    public function cargoFilterOptions(): array
    {
        return $this->distinctColumnOptions('ficha.position_name', 'Todos');
    }

    /**
     * Opciones de filtro puesto (cost_center_name en Ficha).
     *
     * @return list<array{value: string, label: string}>
     */
    public function puestoFilterOptions(): array
    {
        return $this->distinctColumnOptions('ficha.cost_center_name', 'Todos');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function distinctColumnOptions(string $qualifiedColumn, string $allLabel): array
    {
        // Solo columnas ficha.* permitidas (evitar inyección en selectRaw).
        $allowed = [
            'ficha.position_name',
            'ficha.cost_center_name',
        ];
        if (! in_array($qualifiedColumn, $allowed, true)) {
            return [['value' => 'todos', 'label' => $allLabel]];
        }

        return $this->distinctExpressionOptions($qualifiedColumn, $allLabel);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function distinctExpressionOptions(string $expression, string $allLabel): array
    {
        $values = MtSt04Registro::query()
            ->join(
                'employee_ficha_profiles as ficha',
                'ficha.document_number',
                '=',
                'mt_st_04_registros.document_number',
            )
            ->selectRaw("{$expression} as filter_value")
            ->whereRaw("{$expression} is not null")
            ->whereRaw("TRIM({$expression}) <> ''")
            ->distinct()
            ->orderBy('filter_value')
            ->pluck('filter_value')
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->values();

        $options = [
            ['value' => 'todos', 'label' => $allLabel],
        ];

        foreach ($values as $value) {
            $options[] = ['value' => $value, 'label' => $value];
        }

        return $options;
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

        // Solo filas sin match en Ficha (import/formulario sin ficha).
        if ($fichaEstado === 'sin_ficha') {
            $query->whereNull('ficha.id');

            return;
        }

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        // Activos: incluye cédulas sin Ficha para que no queden ocultas tras el import.
        if ($fichaEstado === EmployeeFichaProfile::STATUS_ACTIVO) {
            $query->where(function (Builder $inner): void {
                $inner->where('ficha.employment_status', EmployeeFichaProfile::STATUS_ACTIVO)
                    ->orWhereNull('ficha.id');
            });

            return;
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
