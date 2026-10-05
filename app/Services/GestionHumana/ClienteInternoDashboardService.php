<?php

namespace App\Services\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ClienteInternoDashboardService
{
    private const MONTH_SHORT_LABELS = [
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

    public const SIN_ESTADO_LABEL = 'Sin estado';

    /**
     * KPIs del Dashboard Cliente interno.
     *
     * Default de año:
     * 1) Si $anio es válido (2000–2100) → usarlo.
     * 2) Si el año calendario actual tiene registros → año actual.
     * 3) Si hay datos en cualquier año → MAX(anio) con datos.
     * 4) Sin datos → año calendario actual (métricas en cero).
     *
     * @param  array{mes?: int|string|null}  $filters
     * @return array{
     *     total: int,
     *     por_estado: list<array{estado: string, total: int}>,
     *     dias_respuesta: array{
     *         promedio: float|null,
     *         con_dato: int,
     *         distribucion: list<array{label: string, total: int}>
     *     },
     *     tendencia_mensual: array<int, int>,
     *     anio: int,
     *     filters: array{anio: string, mes: string},
     *     charts: array{
     *         por_estado: array{labels: list<string>, data: list<int>},
     *         dias_respuesta: array{labels: list<string>, data: list<int>},
     *         tendencia_mensual: array{labels: list<string>, data: list<int>}
     *     }
     * }
     */
    public function metrics(?int $anio = null, array $filters = []): array
    {
        $resolvedAnio = $this->resolveAnio($anio);
        $mes = $this->normalizeMes($filters['mes'] ?? null);

        $base = ClienteInternoSolicitud::query()->where('anio', $resolvedAnio);
        if ($mes !== null) {
            $base->where('mes', $mes);
        }

        $total = (clone $base)->count();
        $porEstado = $this->buildPorEstado(clone $base);
        $diasRespuesta = $this->buildDiasRespuesta(clone $base);
        // Tendencia siempre del año completo (el filtro mes no la reduce).
        $tendencia = $this->buildTendenciaMensual($resolvedAnio);

        return [
            'total' => $total,
            'por_estado' => $porEstado['rows'],
            'dias_respuesta' => $diasRespuesta['payload'],
            'tendencia_mensual' => $tendencia['counts'],
            'anio' => $resolvedAnio,
            'filters' => [
                'anio' => (string) $resolvedAnio,
                'mes' => $mes !== null ? (string) $mes : '',
            ],
            'charts' => [
                'por_estado' => [
                    'labels' => $porEstado['labels'],
                    'data' => $porEstado['data'],
                ],
                'dias_respuesta' => [
                    'labels' => $diasRespuesta['labels'],
                    'data' => $diasRespuesta['data'],
                ],
                'tendencia_mensual' => [
                    'labels' => $tendencia['labels'],
                    'data' => $tendencia['data'],
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>
     * }
     */
    public function filterSelectOptions(?int $selectedAnio = null): array
    {
        $meses = [];
        foreach (ClienteInternoDatatableService::MESES as $value => $label) {
            $meses[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        return [
            'anios' => $this->yearSelectOptions($selectedAnio),
            'meses' => $meses,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function yearSelectOptions(?int $selectedAnio = null): array
    {
        $years = ClienteInternoSolicitud::query()
            ->select('anio')
            ->whereNotNull('anio')
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
     * @param  Builder<ClienteInternoSolicitud>  $base
     * @return array{
     *     rows: list<array{estado: string, total: int}>,
     *     labels: list<string>,
     *     data: list<int>
     * }
     */
    private function buildPorEstado(Builder $base): array
    {
        $grouped = (clone $base)
            ->select('estado_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('estado_id')
            ->get();

        /** @var array<int, int> $countsByEstadoId */
        $countsByEstadoId = [];
        $sinEstado = 0;

        foreach ($grouped as $row) {
            $count = (int) $row->aggregate;
            if ($row->estado_id === null) {
                $sinEstado = $count;

                continue;
            }
            $countsByEstadoId[(int) $row->estado_id] = $count;
        }

        $estados = ClienteInternoEstado::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $seenIds = [];
        $rows = [];
        $labels = [];
        $data = [];

        foreach ($estados as $estado) {
            $id = (int) $estado->id;
            $seenIds[$id] = true;
            $count = $countsByEstadoId[$id] ?? 0;
            $rows[] = ['estado' => (string) $estado->name, 'total' => $count];
            $labels[] = (string) $estado->name;
            $data[] = $count;
        }

        // Estados huérfanos (fuera del catálogo actual pero aún referenciados).
        foreach ($countsByEstadoId as $estadoId => $aggregate) {
            if (isset($seenIds[$estadoId])) {
                continue;
            }
            $name = 'Estado #'.$estadoId;
            $rows[] = ['estado' => $name, 'total' => $aggregate];
            $labels[] = $name;
            $data[] = $aggregate;
        }

        $rows[] = ['estado' => self::SIN_ESTADO_LABEL, 'total' => $sinEstado];
        $labels[] = self::SIN_ESTADO_LABEL;
        $data[] = $sinEstado;

        return [
            'rows' => $rows,
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Solo filas con dias_respuesta no null entran al promedio y a la distribución.
     *
     * @param  Builder<ClienteInternoSolicitud>  $base
     * @return array{
     *     payload: array{
     *         promedio: float|null,
     *         con_dato: int,
     *         distribucion: list<array{label: string, total: int}>
     *     },
     *     labels: list<string>,
     *     data: list<int>
     * }
     */
    private function buildDiasRespuesta(Builder $base): array
    {
        $bins = $this->diasBins();
        $bucketTotals = array_fill(0, count($bins), 0);

        $withDias = (clone $base)->whereNotNull('dias_respuesta');
        $conDato = (clone $withDias)->count();
        $promedio = null;

        if ($conDato > 0) {
            $avg = (clone $withDias)->avg('dias_respuesta');
            $promedio = $avg !== null ? round((float) $avg, 1) : null;

            (clone $withDias)
                ->select(['id', 'dias_respuesta'])
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use (&$bucketTotals, $bins): void {
                    foreach ($rows as $row) {
                        $dias = (int) $row->dias_respuesta;
                        $index = $this->binIndexFor($dias, $bins);
                        if ($index !== null) {
                            $bucketTotals[$index]++;
                        }
                    }
                });
        }

        $distribucion = [];
        $labels = [];
        $data = [];
        foreach ($bins as $index => $bin) {
            $total = (int) ($bucketTotals[$index] ?? 0);
            $label = (string) $bin['label'];
            $distribucion[] = ['label' => $label, 'total' => $total];
            $labels[] = $label;
            $data[] = $total;
        }

        return [
            'payload' => [
                'promedio' => $promedio,
                'con_dato' => $conDato,
                'distribucion' => $distribucion,
            ],
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @return array{
     *     counts: array<int, int>,
     *     labels: list<string>,
     *     data: list<int>
     * }
     */
    private function buildTendenciaMensual(int $anio): array
    {
        $mesCounts = ClienteInternoSolicitud::query()
            ->where('anio', $anio)
            ->select('mes', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mes')
            ->pluck('aggregate', 'mes');

        $counts = [];
        $labels = [];
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $count = (int) ($mesCounts[$month] ?? 0);
            $counts[$month] = $count;
            $labels[] = self::MONTH_SHORT_LABELS[$month];
            $data[] = $count;
        }

        return [
            'counts' => $counts,
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @return list<array{max: int|null, label: string}>
     */
    private function diasBins(): array
    {
        $configured = config('cliente_interno.dashboard.dias_bins', []);
        if (! is_array($configured) || $configured === []) {
            return [
                ['max' => 2, 'label' => '0–2'],
                ['max' => 5, 'label' => '3–5'],
                ['max' => 10, 'label' => '6–10'],
                ['max' => null, 'label' => '11+'],
            ];
        }

        $bins = [];
        foreach ($configured as $bin) {
            if (! is_array($bin) || ! isset($bin['label'])) {
                continue;
            }
            $bins[] = [
                'max' => array_key_exists('max', $bin) && $bin['max'] !== null
                    ? (int) $bin['max']
                    : null,
                'label' => (string) $bin['label'],
            ];
        }

        return $bins !== [] ? $bins : [
            ['max' => 2, 'label' => '0–2'],
            ['max' => 5, 'label' => '3–5'],
            ['max' => 10, 'label' => '6–10'],
            ['max' => null, 'label' => '11+'],
        ];
    }

    /**
     * @param  list<array{max: int|null, label: string}>  $bins
     */
    private function binIndexFor(int $dias, array $bins): ?int
    {
        foreach ($bins as $index => $bin) {
            if ($bin['max'] === null || $dias <= $bin['max']) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeMes(mixed $mes): ?int
    {
        if ($mes === null || $mes === '') {
            return null;
        }

        $mesInt = (int) $mes;

        return ($mesInt >= 1 && $mesInt <= 12) ? $mesInt : null;
    }

    private function resolveAnio(?int $anio): int
    {
        if ($anio !== null && $anio >= 2000 && $anio <= 2100) {
            return $anio;
        }

        $current = (int) now()->year;

        $hasCurrent = ClienteInternoSolicitud::query()
            ->where('anio', $current)
            ->exists();

        if ($hasCurrent) {
            return $current;
        }

        $latestWithData = ClienteInternoSolicitud::query()->max('anio');

        if ($latestWithData !== null) {
            return (int) $latestWithData;
        }

        return $current;
    }
}
