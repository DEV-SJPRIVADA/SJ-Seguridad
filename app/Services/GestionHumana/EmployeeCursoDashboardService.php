<?php

namespace App\Services\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeeCursoDashboardService
{
    public function __construct(
        private readonly EmployeeCursoListService $listService,
    ) {}

    /**
     * @param  array{
     *     curso_tipo_id?: string,
     *     vigencia?: string,
     *     estado?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     anio?: int,
     * }  $filters
     * @return array{
     *     kpis: array<string, int>,
     *     charts: array<string, mixed>,
     *     filters: array<string, mixed>
     * }
     */
    public function metrics(array $filters): array
    {
        $anio = (int) ($filters['anio'] ?? now()->year);
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) now()->year;
        }

        /** @var Collection<int, EmployeeCurso> $rows */
        $rows = $this->listService->filteredQuery([
            'curso_tipo_id' => $filters['curso_tipo_id'] ?? '',
            'vigencia' => $filters['vigencia'] ?? '',
            'estado' => $filters['estado'] ?? 'todos',
            'fecha_desde' => $filters['fecha_desde'] ?? '',
            'fecha_hasta' => $filters['fecha_hasta'] ?? '',
        ], ordered: false)
            ->with(['cursoTipo:id,tipo_curso'])
            ->get([
                'id',
                'curso_tipo_id',
                'fecha_expedicion',
                'estado',
                'document_path',
                'created_at',
                'updated_at',
            ]);

        $total = $rows->count();
        $byVigencia = $rows->countBy(fn (EmployeeCurso $curso): string => $curso->computeVigencia());
        $vigentes = (int) ($byVigencia[EmployeeCurso::VIGENCIA_VIGENTE] ?? 0);
        $actualizar = (int) ($byVigencia[EmployeeCurso::VIGENCIA_ACTUALIZAR] ?? 0);
        $vencidos = (int) ($byVigencia[EmployeeCurso::VIGENCIA_VENCIDO] ?? 0);
        $sinDocumento = $rows->filter(fn (EmployeeCurso $curso): bool => ! $curso->hasDocument())->count();
        $solicitados = $rows->where('estado', EmployeeCurso::ESTADO_SOLICITADO)->count();
        $actualizadosEstado = $rows->where('estado', EmployeeCurso::ESTADO_ACTUALIZADO)->count();
        $pendientes = $rows->where('estado', EmployeeCurso::ESTADO_PENDIENTE)->count();

        $byTipo = $rows
            ->groupBy(fn (EmployeeCurso $curso): string => $curso->cursoTipo?->tipo_curso ?: 'Sin tipo')
            ->map->count()
            ->sortDesc()
            ->take(12);

        $byEstado = $rows
            ->groupBy(fn (EmployeeCurso $curso): string => $curso->estado ?: '(Sin estado)')
            ->map->count()
            ->sortDesc();

        $nuevosPorMes = array_fill(1, 12, 0);
        $actualizadosPorMes = array_fill(1, 12, 0);

        foreach ($rows as $curso) {
            if ($curso->created_at instanceof Carbon && (int) $curso->created_at->year === $anio) {
                $nuevosPorMes[(int) $curso->created_at->month]++;
            }

            if (
                $curso->updated_at instanceof Carbon
                && (int) $curso->updated_at->year === $anio
                && $curso->created_at instanceof Carbon
                && ! $curso->updated_at->isSameDay($curso->created_at)
            ) {
                $actualizadosPorMes[(int) $curso->updated_at->month]++;
            }
        }

        $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return [
            'kpis' => [
                'total' => $total,
                'vigentes' => $vigentes,
                'actualizar' => $actualizar,
                'vencidos' => $vencidos,
                'sin_documento' => $sinDocumento,
                'solicitados' => $solicitados,
                'actualizados' => $actualizadosEstado,
                'pendientes' => $pendientes,
            ],
            'charts' => [
                'by_tipo' => [
                    'labels' => $byTipo->keys()->values()->all(),
                    'data' => $byTipo->values()->map(fn ($v) => (int) $v)->all(),
                ],
                'by_vigencia' => [
                    'labels' => [
                        EmployeeCurso::VIGENCIA_VIGENTE,
                        EmployeeCurso::VIGENCIA_ACTUALIZAR,
                        EmployeeCurso::VIGENCIA_VENCIDO,
                    ],
                    'data' => [$vigentes, $actualizar, $vencidos],
                ],
                'by_estado' => [
                    'labels' => $byEstado->keys()->values()->all(),
                    'data' => $byEstado->values()->map(fn ($v) => (int) $v)->all(),
                ],
                'trend' => [
                    'year' => $anio,
                    'labels' => $monthLabels,
                    'nuevos' => collect(range(1, 12))->map(fn (int $m) => (int) $nuevosPorMes[$m])->values()->all(),
                    'actualizados' => collect(range(1, 12))->map(fn (int $m) => (int) $actualizadosPorMes[$m])->values()->all(),
                ],
            ],
            'filters' => [
                'curso_tipo_id' => (string) ($filters['curso_tipo_id'] ?? ''),
                'vigencia' => (string) ($filters['vigencia'] ?? ''),
                'estado' => (string) ($filters['estado'] ?? 'todos'),
                'fecha_desde' => (string) ($filters['fecha_desde'] ?? ''),
                'fecha_hasta' => (string) ($filters['fecha_hasta'] ?? ''),
                'anio' => $anio,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function yearOptions(): array
    {
        $years = EmployeeCurso::query()
            ->orderByDesc('created_at')
            ->limit(500)
            ->pluck('created_at')
            ->filter()
            ->map(fn ($dt) => (int) Carbon::parse($dt)->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $current = (int) now()->year;
        if (! in_array($current, $years, true)) {
            array_unshift($years, $current);
        }

        return $years !== [] ? array_values(array_unique($years)) : [$current];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function tipoOptions(): array
    {
        return CursoTipo::query()
            ->ordered()
            ->get(['id', 'tipo_curso'])
            ->map(fn (CursoTipo $tipo): array => [
                'value' => (string) $tipo->id,
                'label' => $tipo->tipo_curso,
            ])
            ->values()
            ->all();
    }
}
