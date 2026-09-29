<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

final class AcreditacionDashboardService
{
    /**
     * @param  array{
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     cargo_apo?: string,
     *     ficha_estado?: string,
     *     anio?: int,
     * }  $filters
     * @return array{
     *     kpis: array<string, int>,
     *     charts: array<string, mixed>,
     *     filters: array<string, mixed>,
     *     labels: array<string, string>
     * }
     */
    public function metrics(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $base = $this->filteredQuery($normalized);

        $byEstado = (clone $base)
            ->selectRaw('estado, COUNT(*) as aggregate')
            ->groupBy('estado')
            ->pluck('aggregate', 'estado');

        $enProceso = (int) ($byEstado[AcreditacionAcreditado::ESTADO_EN_PROCESO] ?? 0);
        $acreditado = (int) ($byEstado[AcreditacionAcreditado::ESTADO_ACREDITADO] ?? 0);
        $porVencer = (int) ($byEstado[AcreditacionAcreditado::ESTADO_POR_VENCER] ?? 0);
        $desacreditado = (int) ($byEstado[AcreditacionAcreditado::ESTADO_DESACREDITADO] ?? 0);
        $total = $enProceso + $acreditado + $porVencer + $desacreditado;

        /** @var array<string, string> $estadoLabels */
        $estadoLabels = config('acreditaciones.estados', []);

        $estadoOrder = AcreditacionAcreditado::ESTADOS;
        $estadoChartLabels = [];
        $estadoChartData = [];
        foreach ($estadoOrder as $estado) {
            $estadoChartLabels[] = $estadoLabels[$estado] ?? $estado;
            $estadoChartData[] = (int) ($byEstado[$estado] ?? 0);
        }

        $byCargo = (clone $base)
            ->selectRaw("COALESCE(NULLIF(TRIM(cargo_apo), ''), 'Sin cargo') as cargo_label, COUNT(*) as aggregate")
            ->groupByRaw("COALESCE(NULLIF(TRIM(cargo_apo), ''), 'Sin cargo')")
            ->orderByDesc('aggregate')
            ->limit(12)
            ->get();

        $anio = (int) $normalized['anio'];
        $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $trendSolicitudes = $this->trendByMonth((clone $base), $anio, 'fecha_solicitud');
        $trendVencimientos = $this->trendByMonth((clone $base), $anio, 'vigencia_acr');

        return [
            'kpis' => [
                'total' => $total,
                'en_proceso' => $enProceso,
                'acreditado' => $acreditado,
                'por_vencer' => $porVencer,
                'desacreditado' => $desacreditado,
            ],
            'charts' => [
                'by_estado' => [
                    'labels' => $estadoChartLabels,
                    'data' => $estadoChartData,
                ],
                'by_cargo_apo' => [
                    'labels' => $byCargo->pluck('cargo_label')->map(fn ($v) => (string) $v)->values()->all(),
                    'data' => $byCargo->pluck('aggregate')->map(fn ($v) => (int) $v)->values()->all(),
                ],
                'trend' => [
                    'labels' => $monthLabels,
                    'data' => $trendSolicitudes,
                    'anio' => $anio,
                ],
                'trend_vencimientos' => [
                    'labels' => $monthLabels,
                    'data' => $trendVencimientos,
                    'anio' => $anio,
                ],
            ],
            'filters' => $normalized,
            'labels' => [
                'total' => 'Total',
                'en_proceso' => $estadoLabels[AcreditacionAcreditado::ESTADO_EN_PROCESO] ?? 'EN PROCESO',
                'acreditado' => $estadoLabels[AcreditacionAcreditado::ESTADO_ACREDITADO] ?? 'ACREDITADO',
                'por_vencer' => $estadoLabels[AcreditacionAcreditado::ESTADO_POR_VENCER] ?? 'POR VENCER',
                'desacreditado' => $estadoLabels[AcreditacionAcreditado::ESTADO_DESACREDITADO] ?? 'DESACREDITADO',
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function cargoApoOptions(): array
    {
        return AcreditacionCargo::query()
            ->active()
            ->orderBy('cargo_apo')
            ->get(['cargo_apo'])
            ->pluck('cargo_apo')
            ->map(fn (mixed $apo): string => trim((string) $apo))
            ->filter(fn (string $apo): bool => $apo !== '')
            ->unique(fn (string $apo): string => mb_strtolower($apo))
            ->values()
            ->map(fn (string $apo): array => [
                'value' => $apo,
                'label' => $apo,
            ])
            ->all();
    }

    /**
     * @return list<int>
     */
    public function yearOptions(): array
    {
        $current = (int) now()->year;
        $years = range($current, $current - 5);

        $driver = Schema::getConnection()->getDriverName();
        $fromDb = [];

        foreach (['fecha_solicitud', 'vigencia_acr'] as $column) {
            if ($driver === 'sqlite') {
                $rows = AcreditacionAcreditado::query()
                    ->whereNotNull($column)
                    ->selectRaw("DISTINCT CAST(strftime('%Y', {$column}) AS INTEGER) as anio")
                    ->orderByDesc('anio')
                    ->pluck('anio')
                    ->map(fn ($y) => (int) $y)
                    ->filter(fn (int $y): bool => $y >= 2000 && $y <= 2100)
                    ->all();
            } else {
                $rows = AcreditacionAcreditado::query()
                    ->whereNotNull($column)
                    ->selectRaw("DISTINCT YEAR({$column}) as anio")
                    ->orderByDesc('anio')
                    ->pluck('anio')
                    ->map(fn ($y) => (int) $y)
                    ->filter(fn (int $y): bool => $y >= 2000 && $y <= 2100)
                    ->all();
            }

            $fromDb = array_merge($fromDb, $rows);
        }

        return collect(array_merge($years, $fromDb))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     * @param  'fecha_solicitud'|'vigencia_acr'  $dateColumn
     * @return list<int>
     */
    private function trendByMonth(Builder $query, int $anio, string $dateColumn): array
    {
        $allowed = ['fecha_solicitud', 'vigencia_acr'];
        if (! in_array($dateColumn, $allowed, true)) {
            $dateColumn = 'fecha_solicitud';
        }

        $driver = Schema::getConnection()->getDriverName();
        $counts = array_fill(1, 12, 0);

        if ($driver === 'sqlite') {
            $rows = $query
                ->whereNotNull($dateColumn)
                ->whereRaw("CAST(strftime('%Y', {$dateColumn}) AS INTEGER) = ?", [$anio])
                ->selectRaw("CAST(strftime('%m', {$dateColumn}) AS INTEGER) as mes, COUNT(*) as aggregate")
                ->groupBy('mes')
                ->pluck('aggregate', 'mes');
        } else {
            $rows = $query
                ->whereNotNull($dateColumn)
                ->whereYear($dateColumn, $anio)
                ->selectRaw("MONTH({$dateColumn}) as mes, COUNT(*) as aggregate")
                ->groupBy('mes')
                ->pluck('aggregate', 'mes');
        }

        foreach ($rows as $mes => $aggregate) {
            $month = (int) $mes;
            if ($month >= 1 && $month <= 12) {
                $counts[$month] = (int) $aggregate;
            }
        }

        return array_values($counts);
    }

    /**
     * @param  array{
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     cargo_apo: string,
     *     ficha_estado: string,
     *     anio: int,
     * }  $filters
     * @return Builder<AcreditacionAcreditado>
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = AcreditacionAcreditado::query();

        if ($filters['fecha_desde'] !== '') {
            $query->whereDate('fecha_solicitud', '>=', $filters['fecha_desde']);
        }

        if ($filters['fecha_hasta'] !== '') {
            $query->whereDate('fecha_solicitud', '<=', $filters['fecha_hasta']);
        }

        if ($filters['cargo_apo'] !== '') {
            $query->forCargoApo($filters['cargo_apo']);
        }

        $this->applyFichaEstadoFilter($query, $filters['ficha_estado']);

        return $query;
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     */
    private function applyFichaEstadoFilter(Builder $query, string $fichaEstado): void
    {
        if ($fichaEstado === '' || $fichaEstado === 'todos') {
            return;
        }

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        $query->whereExists(function ($sub) use ($fichaEstado): void {
            $sub->selectRaw('1')
                ->from('employee_ficha_profiles')
                ->whereColumn(
                    'employee_ficha_profiles.document_number',
                    'acreditacion_acreditados.document_number',
                )
                ->where('employee_ficha_profiles.employment_status', $fichaEstado);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     cargo_apo: string,
     *     ficha_estado: string,
     *     anio: int,
     * }
     */
    private function normalizeFilters(array $filters): array
    {
        $fechaDesde = $this->normalizeDate($filters['fecha_desde'] ?? null);
        $fechaHasta = $this->normalizeDate($filters['fecha_hasta'] ?? null);

        if ($fechaDesde !== null && $fechaHasta !== null && $fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        $cargoApo = trim((string) ($filters['cargo_apo'] ?? ''));

        $fichaEstado = trim((string) ($filters['ficha_estado'] ?? EmployeeFichaProfile::STATUS_ACTIVO));
        if ($fichaEstado === '') {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }
        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
            'todos',
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        $anio = (int) ($filters['anio'] ?? now()->year);
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) now()->year;
        }

        return [
            'fecha_desde' => $fechaDesde ?? '',
            'fecha_hasta' => $fechaHasta ?? '',
            'cargo_apo' => $cargoApo,
            'ficha_estado' => $fichaEstado,
            'anio' => $anio,
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
