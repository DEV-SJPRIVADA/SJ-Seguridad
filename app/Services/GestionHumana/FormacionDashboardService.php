<?php

namespace App\Services\GestionHumana;

use App\Models\FormacionRegistro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class FormacionDashboardService
{
    private const MONTH_LABELS = [
        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic',
    ];

    private const MONTH_FULL_LABELS = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * KPIs del Dashboard Formación.
     *
     * Default de año (documentado):
     * 1) Si $anio es válido (2000–2100) → usarlo.
     * 2) Si el año calendario actual tiene registros → año actual.
     * 3) Si hay datos en cualquier año → MAX(anio) con datos.
     * 4) Sin datos → año calendario actual (métricas en cero).
     *
     * @param  array{
     *     mes?: int|string|null,
     *     estado?: string|null,
     *     nombre_curso?: string|null,
     * }  $filters
     * @return array{
     *     total: int,
     *     por_mes: array<int, int>,
     *     por_categoria: list<array{categoria: string, total: int}>,
     *     por_estado: array{aprobado: int, reprobado: int, no_realizada: int},
     *     anio: int,
     *     filters: array{anio: string, mes: string, estado: string, nombre_curso: string},
     *     options: array{cursos: list<array{value: string, label: string}>},
     *     charts: array{
     *         por_mes: array{labels: list<string>, data: list<int>},
     *         por_categoria: array{labels: list<string>, data: list<int>},
     *         por_estado: array{labels: list<string>, data: list<int>}
     *     }
     * }
     */
    public function metrics(?int $anio = null, array $filters = []): array
    {
        $resolvedAnio = $this->resolveAnio($anio);
        $topN = max(1, (int) config('formacion.dashboard.categoria_top', 10));

        $mes = $this->normalizeMes($filters['mes'] ?? null);
        $estado = $this->normalizeEstado($filters['estado'] ?? null);
        $nombreCurso = trim((string) ($filters['nombre_curso'] ?? ''));

        $cursoOptions = $this->courseOptions($resolvedAnio, $mes);
        $cursoValues = array_column($cursoOptions, 'value');
        if ($nombreCurso !== '' && ! in_array($nombreCurso, $cursoValues, true)) {
            $nombreCurso = '';
        }

        $base = FormacionRegistro::query()->where('anio', $resolvedAnio);
        $this->applyOptionalFilters($base, $mes, $estado, $nombreCurso);

        $total = (clone $base)->count();

        $mesCounts = (clone $base)
            ->select('mes', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mes')
            ->pluck('aggregate', 'mes');

        /** @var array<int, int> $porMes */
        $porMes = [];
        $mesLabels = [];
        $mesData = [];
        for ($month = 1; $month <= 12; $month++) {
            $count = (int) ($mesCounts[$month] ?? 0);
            $porMes[$month] = $count;
            $mesLabels[] = self::MONTH_LABELS[$month];
            $mesData[] = $count;
        }

        $categoriaRows = (clone $base)
            ->select('categoria', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('categoria')
            ->orderByDesc('aggregate')
            ->orderBy('categoria')
            ->limit($topN)
            ->get();

        /** @var list<array{categoria: string, total: int}> $porCategoria */
        $porCategoria = $categoriaRows
            ->map(static fn ($row): array => [
                'categoria' => (string) $row->categoria,
                'total' => (int) $row->aggregate,
            ])
            ->values()
            ->all();

        $porEstado = [
            FormacionRegistro::ESTADO_APROBADO => (clone $base)->withEstado(FormacionRegistro::ESTADO_APROBADO)->count(),
            FormacionRegistro::ESTADO_REPROBADO => (clone $base)->withEstado(FormacionRegistro::ESTADO_REPROBADO)->count(),
            FormacionRegistro::ESTADO_NO_REALIZADA => (clone $base)->withEstado(FormacionRegistro::ESTADO_NO_REALIZADA)->count(),
        ];

        $estadoLabels = [
            FormacionRegistro::ESTADO_LABELS[FormacionRegistro::ESTADO_APROBADO],
            FormacionRegistro::ESTADO_LABELS[FormacionRegistro::ESTADO_REPROBADO],
            FormacionRegistro::ESTADO_LABELS[FormacionRegistro::ESTADO_NO_REALIZADA],
        ];
        $estadoData = [
            $porEstado[FormacionRegistro::ESTADO_APROBADO],
            $porEstado[FormacionRegistro::ESTADO_REPROBADO],
            $porEstado[FormacionRegistro::ESTADO_NO_REALIZADA],
        ];

        return [
            'total' => $total,
            'por_mes' => $porMes,
            'por_categoria' => $porCategoria,
            'por_estado' => $porEstado,
            'anio' => $resolvedAnio,
            'filters' => [
                'anio' => (string) $resolvedAnio,
                'mes' => $mes !== null ? (string) $mes : '',
                'estado' => $estado,
                'nombre_curso' => $nombreCurso,
            ],
            'options' => [
                'cursos' => $cursoOptions,
            ],
            'charts' => [
                'por_mes' => [
                    'labels' => $mesLabels,
                    'data' => $mesData,
                ],
                'por_categoria' => [
                    'labels' => array_column($porCategoria, 'categoria'),
                    'data' => array_column($porCategoria, 'total'),
                ],
                'por_estado' => [
                    'labels' => $estadoLabels,
                    'data' => $estadoData,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>,
     *     estados: list<array{value: string, label: string}>,
     *     cursos: list<array{value: string, label: string}>
     * }
     */
    public function filterSelectOptions(?int $selectedAnio = null, ?int $selectedMes = null): array
    {
        $meses = [];
        foreach (self::MONTH_FULL_LABELS as $value => $label) {
            $meses[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        $estados = [];
        foreach (FormacionRegistro::ESTADO_LABELS as $value => $label) {
            $estados[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return [
            'anios' => $this->yearSelectOptions($selectedAnio),
            'meses' => $meses,
            'estados' => $estados,
            'cursos' => $this->courseOptions($selectedAnio, $selectedMes),
        ];
    }

    /**
     * Cursos distinct del año (y mes si se indica). Sin mes → todos los del año.
     *
     * @return list<array{value: string, label: string}>
     */
    public function courseOptions(?int $anio = null, ?int $mes = null): array
    {
        $query = FormacionRegistro::query()
            ->select('nombre_curso')
            ->whereNotNull('nombre_curso')
            ->where('nombre_curso', '!=', '')
            ->distinct()
            ->orderBy('nombre_curso');

        if ($anio !== null && $anio >= 2000 && $anio <= 2100) {
            $query->where('anio', $anio);
        }

        if ($mes !== null && $mes >= 1 && $mes <= 12) {
            $query->where('mes', $mes);
        }

        return $query
            ->pluck('nombre_curso')
            ->map(static fn ($curso): array => [
                'value' => (string) $curso,
                'label' => (string) $curso,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function yearSelectOptions(?int $selectedAnio = null): array
    {
        $years = FormacionRegistro::query()
            ->select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(static fn ($year): int => (int) $year)
            ->filter(static fn (int $year): bool => $year >= 2000 && $year <= 2100)
            ->values()
            ->all();

        $current = (int) now()->year;
        if (! in_array($current, $years, true)) {
            $years[] = $current;
        }

        if ($selectedAnio !== null && $selectedAnio >= 2000 && $selectedAnio <= 2100
            && ! in_array($selectedAnio, $years, true)) {
            $years[] = $selectedAnio;
        }

        rsort($years);

        return array_map(
            static fn (int $year): array => [
                'value' => (string) $year,
                'label' => (string) $year,
            ],
            $years,
        );
    }

    /**
     * @param  Builder<FormacionRegistro>  $query
     */
    private function applyOptionalFilters(Builder $query, ?int $mes, string $estado, string $nombreCurso): void
    {
        if ($mes !== null) {
            $query->where('mes', $mes);
        }

        if ($estado !== '') {
            $query->withEstado($estado);
        }

        if ($nombreCurso !== '') {
            $query->where('nombre_curso', $nombreCurso);
        }
    }

    private function normalizeMes(mixed $mes): ?int
    {
        if ($mes === null || $mes === '') {
            return null;
        }

        $mesInt = (int) $mes;

        return ($mesInt >= 1 && $mesInt <= 12) ? $mesInt : null;
    }

    private function normalizeEstado(mixed $estado): string
    {
        $key = strtolower(trim((string) ($estado ?? '')));

        return array_key_exists($key, FormacionRegistro::ESTADO_LABELS) ? $key : '';
    }

    private function resolveAnio(?int $anio): int
    {
        if ($anio !== null && $anio >= 2000 && $anio <= 2100) {
            return $anio;
        }

        $current = (int) now()->year;

        $hasCurrent = FormacionRegistro::query()
            ->where('anio', $current)
            ->exists();

        if ($hasCurrent) {
            return $current;
        }

        $latestWithData = FormacionRegistro::query()
            ->max('anio');

        if ($latestWithData !== null) {
            return (int) $latestWithData;
        }

        return $current;
    }
}
