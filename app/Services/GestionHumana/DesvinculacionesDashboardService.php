<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Informe mensual del Dashboard de Desvinculaciones (KPI + gráficos).
 * Fuente: periodos cerrados con termination_date + ficha para ciudad/cliente.
 */
final class DesvinculacionesDashboardService
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

    private const TOP_N = 12;

    /**
     * @param  array{
     *     anio?: int|string|null,
     *     mes?: int|string|null,
     *     ciudad?: string|null,
     *     cargo?: string|null,
     *     cliente?: string|null,
     * }  $filters
     * @return array{
     *     filters: array{anio: string, mes: string, ciudad: string, cargo: string, cliente: string},
     *     kpis: array<string, int|string>,
     *     charts: array<string, mixed>,
     *     meta: array{filters_nota: string}
     * }
     */
    public function metrics(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $anio = (int) $normalized['anio'];
        $mes = (int) $normalized['mes'];

        $bajasMes = $this->countInRange($anio, $mes, $mes, $normalized);
        $bajasAnio = $this->countInRange($anio, 1, $mes, $normalized);
        $topCausal = $this->topCausalInMonth($anio, $mes, $normalized);

        return [
            'filters' => $normalized,
            'kpis' => [
                'bajas_mes' => $bajasMes,
                'bajas_anio' => $bajasAnio,
                'top_causal_mes' => $topCausal['count'],
                'top_causal_label' => $topCausal['label'],
            ],
            'charts' => [
                'evolucion_mensual' => $this->chartEvolucionMensual($anio, $normalized),
                'por_causal' => $this->chartGrouped($anio, $mes, $normalized, 'causal'),
                'por_ciudad' => $this->chartGrouped($anio, $mes, $normalized, 'ciudad'),
                'por_cliente' => $this->chartGrouped($anio, $mes, $normalized, 'cliente'),
                'por_cargo' => $this->chartGrouped($anio, $mes, $normalized, 'cargo'),
            ],
            'meta' => [
                'filters_nota' => 'Las desvinculaciones usan fecha de desvinculación del periodo. Ciudad/cliente vienen de la ficha.',
            ],
        ];
    }

    /**
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>,
     *     ciudades: list<array{value: string, label: string}>,
     *     cargos: list<array{value: string, label: string}>,
     *     clientes: list<array{value: string, label: string}>,
     * }
     */
    public function filterSelectOptions(?int $selectedAnio = null): array
    {
        return [
            'anios' => $this->yearSelectOptions($selectedAnio),
            'meses' => collect(self::MONTH_FULL_LABELS)
                ->map(static fn (string $label, int $month): array => [
                    'value' => (string) $month,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'ciudades' => $this->cityOptions(),
            'cargos' => $this->positionOptions(),
            'clientes' => $this->clientOptions(),
        ];
    }

    /**
     * @param  array{
     *     anio?: int|string|null,
     *     mes?: int|string|null,
     *     ciudad?: string|null,
     *     cargo?: string|null,
     *     cliente?: string|null,
     * }  $filters
     * @return array{anio: string, mes: string, ciudad: string, cargo: string, cliente: string}
     */
    public function normalizeFilters(array $filters): array
    {
        $now = now('America/Bogota');
        $anio = (int) ($filters['anio'] ?? $now->year);
        $mes = (int) ($filters['mes'] ?? $now->month);

        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) $now->year;
        }

        if ($mes < 1 || $mes > 12) {
            $mes = (int) $now->month;
        }

        return [
            'anio' => (string) $anio,
            'mes' => (string) $mes,
            'ciudad' => trim((string) ($filters['ciudad'] ?? '')),
            'cargo' => trim((string) ($filters['cargo'] ?? '')),
            'cliente' => trim((string) ($filters['cliente'] ?? '')),
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countInRange(int $anio, int $fromMonth, int $toMonth, array $filters): int
    {
        return (int) $this->baseQuery($filters)
            ->whereYear('periods.termination_date', $anio)
            ->whereMonth('periods.termination_date', '>=', $fromMonth)
            ->whereMonth('periods.termination_date', '<=', $toMonth)
            ->distinct()
            ->count('periods.id');
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{label: string, count: int}
     */
    private function topCausalInMonth(int $anio, int $mes, array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->whereYear('periods.termination_date', $anio)
            ->whereMonth('periods.termination_date', $mes)
            ->selectRaw($this->causalLabelSql().' as label')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->orderBy('label')
            ->first();

        if ($row === null) {
            return ['label' => '—', 'count' => 0];
        }

        return [
            'label' => (string) ($row->label ?: 'Sin causal'),
            'count' => (int) $row->total,
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartEvolucionMensual(int $anio, array $filters): array
    {
        $labels = [];
        $data = [];
        $lastMonth = $this->lastTrendMonthForYear($anio);

        for ($month = 1; $month <= $lastMonth; $month++) {
            $labels[] = self::MONTH_LABELS[$month];
            $data[] = $this->countInRange($anio, $month, $month, $filters);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @param  'causal'|'ciudad'|'cliente'|'cargo'  $dimension
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartGrouped(int $anio, int $mes, array $filters, string $dimension): array
    {
        $expr = match ($dimension) {
            'causal' => $this->causalLabelSql(),
            'ciudad' => EmployeeFichaProfile::displayCitySql('ficha'),
            'cliente' => GestionHumanaAreaDashboardService::displayClientSql('ficha'),
            'cargo' => "COALESCE(NULLIF(TRIM(periods.position_name), ''), 'Sin cargo')",
        };

        $rows = $this->baseQuery($filters)
            ->whereYear('periods.termination_date', $anio)
            ->whereMonth('periods.termination_date', $mes)
            ->selectRaw("{$expr} as label")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->orderBy('label')
            ->limit(self::TOP_N)
            ->get();

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row->label ?? ''));
            if ($label === '') {
                $label = match ($dimension) {
                    'causal' => 'Sin causal',
                    'ciudad' => 'Sin ciudad',
                    'cliente' => 'Sin cliente',
                    'cargo' => 'Sin cargo',
                };
            }
            $labels[] = $label;
            $data[] = (int) $row->total;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return Builder<EmployeeFichaEmploymentPeriod>
     */
    private function baseQuery(array $filters): Builder
    {
        $query = EmployeeFichaEmploymentPeriod::query()
            ->from('employee_ficha_employment_periods as periods')
            ->leftJoin(
                'employee_ficha_profiles as ficha',
                'ficha.personal_requisition_ficha_entry_id',
                '=',
                'periods.personal_requisition_ficha_entry_id'
            )
            ->whereNotNull('periods.termination_date');

        if ($filters['ciudad'] !== '') {
            $query->whereRaw(EmployeeFichaProfile::displayCitySql('ficha').' = ?', [$filters['ciudad']]);
        }

        if ($filters['cargo'] !== '') {
            $query->where('periods.position_name', $filters['cargo']);
        }

        if ($filters['cliente'] !== '') {
            $query->whereRaw(GestionHumanaAreaDashboardService::displayClientSql('ficha').' = ?', [$filters['cliente']]);
        }

        return $query;
    }

    private function causalLabelSql(): string
    {
        return "COALESCE(NULLIF(TRIM(periods.termination_cause_name), ''), NULLIF(TRIM(periods.termination_cause_code), ''), 'Sin causal')";
    }

    private function lastTrendMonthForYear(int $anio): int
    {
        $now = now('America/Bogota');
        $currentYear = (int) $now->year;

        if ($anio < $currentYear) {
            return 12;
        }

        if ($anio > $currentYear) {
            return 0;
        }

        return max(1, min(12, (int) $now->month));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function yearSelectOptions(?int $selectedAnio = null): array
    {
        $current = (int) now('America/Bogota')->year;
        $years = range($current, $current - 5);

        $driver = DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite'
            ? "CAST(strftime('%Y', periods.termination_date) AS INTEGER)"
            : 'YEAR(periods.termination_date)';

        $fromData = EmployeeFichaEmploymentPeriod::query()
            ->from('employee_ficha_employment_periods as periods')
            ->whereNotNull('periods.termination_date')
            ->selectRaw("{$yearExpr} as anio")
            ->distinct()
            ->pluck('anio')
            ->map(static fn ($y): int => (int) $y)
            ->filter(static fn (int $y): bool => $y >= 2000 && $y <= 2100)
            ->all();

        $years = array_values(array_unique(array_merge($years, $fromData)));

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
     * @return list<array{value: string, label: string}>
     */
    private function cityOptions(): array
    {
        $citySql = EmployeeFichaProfile::displayCitySql();

        return EmployeeFichaProfile::query()
            ->selectRaw("{$citySql} as city_label")
            ->whereRaw("{$citySql} IS NOT NULL")
            ->whereRaw("TRIM({$citySql}) != ''")
            ->distinct()
            ->orderBy('city_label')
            ->pluck('city_label')
            ->map(static fn ($city): array => [
                'value' => (string) $city,
                'label' => (string) $city,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function positionOptions(): array
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->select('position_name')
            ->whereNotNull('termination_date')
            ->whereNotNull('position_name')
            ->where('position_name', '!=', '')
            ->distinct()
            ->orderBy('position_name')
            ->pluck('position_name')
            ->map(static fn ($name): array => [
                'value' => (string) $name,
                'label' => (string) $name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function clientOptions(): array
    {
        $clientSql = GestionHumanaAreaDashboardService::displayClientSql();

        return EmployeeFichaProfile::query()
            ->selectRaw("{$clientSql} as client_label")
            ->whereRaw("{$clientSql} IS NOT NULL")
            ->whereRaw("TRIM({$clientSql}) != ''")
            ->distinct()
            ->orderBy('client_label')
            ->pluck('client_label')
            ->map(static fn ($client): array => [
                'value' => (string) $client,
                'label' => (string) $client,
            ])
            ->values()
            ->all();
    }
}
