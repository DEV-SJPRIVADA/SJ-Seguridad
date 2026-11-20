<?php

namespace App\Services\Calidad;

use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Solicitud;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPIs y series del dashboard MT-GI-11.
 *
 * Todas las consultas respetan el alcance del usuario (propias o todas),
 * igual que el listado y el export.
 */
final class MtGi11DashboardService
{
    public function __construct(
        private readonly MtGi11DatatableService $datatable,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function metrics(array $filters, int $userId, bool $viewAll): array
    {
        $query = $this->aggregateQuery($filters, $userId, $viewAll);

        $estados = MtGi11Estado::query()->ordered()->get(['id', 'code', 'name', 'color']);

        // Agregación explícita: solo las columnas del GROUP BY, sin `select *`
        // ni el subquery de withCount (MySQL con only_full_group_by lo rechaza).
        $byEstado = (clone $query)
            ->reorder()
            ->selectRaw('estado_id, COUNT(*) as total')
            ->groupBy('estado_id')
            ->toBase()
            ->pluck('total', 'estado_id');

        $kpis = ['total' => (clone $query)->count()];

        foreach ($estados as $estado) {
            $kpis['estado_'.$estado->id] = (int) ($byEstado[$estado->id] ?? 0);
        }

        return [
            'kpis' => $kpis,
            'estados' => $estados->map(fn (MtGi11Estado $estado): array => [
                'id' => (int) $estado->id,
                'label' => (string) $estado->name,
                'color' => $estado->pillClass(),
                'total' => (int) ($byEstado[$estado->id] ?? 0),
            ])->all(),
            'proceso' => $this->countsByRelation($query, 'proceso', 'calidad_mt_gi_11_procesos', 'proceso_id'),
            'tipo_documento' => $this->countsByRelation($query, 'tipo_documento', 'calidad_mt_gi_11_tipos_documento', 'tipo_documento_id'),
        ];
    }

    /**
     * Query base para agregaciones: sin eager loads, sin `withCount` y con
     * `select` limpio, de modo que agrupar no viole `only_full_group_by`.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<MtGi11Solicitud>
     */
    private function aggregateQuery(array $filters, int $userId, bool $viewAll): Builder
    {
        $query = MtGi11Solicitud::query()->visibleTo($userId, $viewAll);

        $this->datatable->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Conteo agrupado por nombre de catálogo.
     *
     * @param  Builder<MtGi11Solicitud>  $query
     * @return array{labels: list<string>, data: list<int>}
     */
    private function countsByRelation(Builder $query, string $relation, string $table, string $foreignKey): array
    {
        $builder = (clone $query)->reorder();
        $builder->getQuery()->columns = null;

        $rows = $builder
            ->leftJoin($table, $table.'.id', '=', 'calidad_mt_gi_11_solicitudes.'.$foreignKey)
            ->selectRaw($table.'.name as label, COUNT(calidad_mt_gi_11_solicitudes.id) as total')
            ->groupBy($table.'.name')
            ->orderByDesc('total')
            ->toBase()
            ->get();

        return [
            'labels' => $rows->pluck('label')->map(fn ($label): string => (string) ($label ?: 'Sin clasificar'))->all(),
            'data' => $rows->pluck('total')->map(fn ($total): int => (int) $total)->all(),
        ];
    }
}
