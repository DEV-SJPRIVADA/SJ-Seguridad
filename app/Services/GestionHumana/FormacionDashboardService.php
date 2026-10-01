<?php

namespace App\Services\GestionHumana;

use App\Models\FormacionRegistro;
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

    /**
     * KPIs del Dashboard Formación.
     *
     * Default de año (documentado):
     * 1) Si $anio es válido (2000–2100) → usarlo.
     * 2) Si el año calendario actual tiene registros → año actual.
     * 3) Si hay datos en cualquier año → MAX(anio) con datos.
     * 4) Sin datos → año calendario actual (métricas en cero).
     *
     * @return array{
     *     total: int,
     *     por_mes: array<int, int>,
     *     por_categoria: list<array{categoria: string, total: int}>,
     *     anio: int,
     *     charts: array{
     *         por_mes: array{labels: list<string>, data: list<int>},
     *         por_categoria: array{labels: list<string>, data: list<int>}
     *     }
     * }
     */
    public function metrics(?int $anio = null): array
    {
        $resolvedAnio = $this->resolveAnio($anio);
        $topN = max(1, (int) config('formacion.dashboard.categoria_top', 10));

        $base = FormacionRegistro::query()->where('anio', $resolvedAnio);

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

        return [
            'total' => $total,
            'por_mes' => $porMes,
            'por_categoria' => $porCategoria,
            'anio' => $resolvedAnio,
            'charts' => [
                'por_mes' => [
                    'labels' => $mesLabels,
                    'data' => $mesData,
                ],
                'por_categoria' => [
                    'labels' => array_column($porCategoria, 'categoria'),
                    'data' => array_column($porCategoria, 'total'),
                ],
            ],
        ];
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
