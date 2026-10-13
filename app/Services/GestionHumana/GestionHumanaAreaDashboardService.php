<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\ClienteInternoSolicitud;
use App\Models\EmployeeArchiveConsultationItem;
use App\Models\EmployeeFichaProfile;
use App\Models\FormacionRegistro;
use App\Models\GhAreaDashboardConceptoCargo;
use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Informe mensual del Dashboard de área Gestión Humana (KPIs + series).
 */
final class GestionHumanaAreaDashboardService
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
     * Cargos operativos canónicos (match trim + case-insensitive).
     *
     * @var list<string>
     */
    public const OPERATIVE_POSITIONS = [
        'ESCOLTA',
        'ESCOLTA MENSUAL',
        'ESCOLTA SJ',
        'GUARDA',
        'GUARDA SJ',
        'GUARDAS MOTORIZADOS',
        'OPERADOR',
        'OPERADOR SJ',
        'Supervisor de puesto',
        'SUPERVISOR SJ',
        'SUPERVISORES',
        'SUPERVISORES MENSUAL',
    ];

    private const FILTERS_PARCIALES_NOTA = 'Cliente interno, Formación y Archivo solo filtran por año/mes.';

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
     *     kpis: array<string, int|float|string>,
     *     charts: array<string, mixed>,
     *     meta: array{activos_mes_actual: int, activos_mes_anterior: int, eom_date: string, filters_parciales_nota: string}
     * }
     */
    public function metrics(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $anio = (int) $normalized['anio'];
        $mes = (int) $normalized['mes'];

        $eom = $this->endOfMonth($anio, $mes);
        $prev = $eom->copy()->subMonthNoOverflow();
        $prevAnio = (int) $prev->year;
        $prevMes = (int) $prev->month;
        $prevEom = $this->endOfMonth($prevAnio, $prevMes);

        $activosActual = $this->countActivosEom($eom, $normalized);
        $activosAnterior = $this->countActivosEom($prevEom, $normalized);
        $ingresosMes = $this->countHiresInMonth($anio, $mes, $normalized);
        $desvinculacionesMes = $this->countTerminationsInMonth($anio, $mes, $normalized);
        $operativo = $this->countOperativosEom($eom, $normalized);

        $rotacion = $this->buildRotacion($desvinculacionesMes, $activosActual, $activosAnterior);
        $operativoPct = $activosActual > 0
            ? round(($operativo / $activosActual) * 100, 1)
            : 0.0;

        $psicofisicos = $this->countPsicofisicosVigente($normalized);
        $acreditaciones = $this->countAcreditacionesAcreditado($normalized);
        $clienteInterno = $this->countClienteInterno($anio, $mes);
        $formacion = $this->countFormacion($anio, $mes);
        $archivo = $this->countArchivoConsultas($anio, $mes);

        return [
            'filters' => $normalized,
            'kpis' => [
                'personal_activo' => $activosActual,
                'ingresos_mes' => $ingresosMes,
                'desvinculaciones_mes' => $desvinculacionesMes,
                'rotacion_mensual_pct' => $rotacion['pct'],
                'rotacion_display' => $rotacion['display'],
                'personal_operativo' => $operativo,
                'personal_operativo_pct' => $operativoPct,
                'acreditaciones' => $acreditaciones,
                'psicofisicos' => $psicofisicos,
                'cliente_interno' => $clienteInterno,
                'formacion' => $formacion,
                'archivo' => $archivo,
            ],
            'charts' => [
                'evolucion_personal' => $this->chartEvolucionPersonal($anio, $normalized),
                'ingresos_vs_desvinculaciones' => $this->chartIngresosVsDesvinculaciones($anio, $normalized),
                'evolucion_formacion' => $this->chartEvolucionFormacion($anio),
                'personal_por_concepto' => $this->chartPersonalPorConcepto($eom, $normalized),
                'estado_psicofisicos' => $this->chartEstadoPsicofisicos($normalized),
                'estado_acreditacion' => $this->chartEstadoAcreditacion($normalized),
                'consultas_archivo' => $this->chartConsultasArchivo($anio, $mes),
            ],
            'meta' => [
                'activos_mes_actual' => $activosActual,
                'activos_mes_anterior' => $activosAnterior,
                'eom_date' => $eom->toDateString(),
                'filters_parciales_nota' => self::FILTERS_PARCIALES_NOTA,
            ],
        ];
    }

    /**
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>,
     *     ciudades: list<array{value: string, label: string}>,
     *     cargos: list<array{value: string, label: string}>,
     *     clientes: list<array{value: string, label: string}>
     * }
     */
    public function filterSelectOptions(?int $selectedAnio = null): array
    {
        $meses = [];
        foreach (self::MONTH_FULL_LABELS as $value => $label) {
            $meses[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        return [
            'anios' => $this->yearSelectOptions($selectedAnio),
            'meses' => $meses,
            'ciudades' => $this->cityOptions(),
            'cargos' => $this->positionOptions(),
            'clientes' => $this->clientOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{anio: string, mes: string, ciudad: string, cargo: string, cliente: string}
     */
    public function normalizeFilters(array $filters): array
    {
        $anio = (int) ($filters['anio'] ?? 0);
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) now('America/Bogota')->year;
        }

        $mes = (int) ($filters['mes'] ?? 0);
        if ($mes < 1 || $mes > 12) {
            $mes = (int) now('America/Bogota')->month;
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
     * @return list<string>
     */
    public static function operativePositionsUpper(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $name): string => mb_strtoupper(trim($name)),
            self::OPERATIVE_POSITIONS,
        )));
    }

    /**
     * Cliente efectivo: work_center primero, si vacío cost_center.
     */
    public static function displayClientSql(string $table = 'employee_ficha_profiles'): string
    {
        return "COALESCE(NULLIF(TRIM({$table}.work_center_name), ''), {$table}.cost_center_name)";
    }

    private function endOfMonth(int $anio, int $mes): Carbon
    {
        return Carbon::create($anio, $mes, 1, 0, 0, 0, 'America/Bogota')->endOfMonth()->startOfDay();
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countActivosEom(Carbon $eom, array $filters): int
    {
        return $this->activosEomQuery($eom, $filters)->count();
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countOperativosEom(Carbon $eom, array $filters): int
    {
        $upper = self::operativePositionsUpper();
        $placeholders = implode(',', array_fill(0, count($upper), '?'));

        return $this->activosEomQuery($eom, $filters)
            ->whereRaw('UPPER(TRIM(position_name)) IN ('.$placeholders.')', $upper)
            ->count();
    }

    /**
     * Persona activa al cierre del mes (EOM).
     *
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return Builder<EmployeeFichaProfile>
     */
    private function activosEomQuery(Carbon $eom, array $filters): Builder
    {
        $eomDate = $eom->toDateString();

        $query = EmployeeFichaProfile::query()
            ->whereNotNull('hire_date')
            ->whereDate('hire_date', '<=', $eomDate)
            ->where(function (Builder $inner) use ($eomDate): void {
                $inner->whereNull('termination_date')
                    ->orWhereDate('termination_date', '>', $eomDate);
            });

        $this->applyFichaDimensionFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countHiresInMonth(int $anio, int $mes, array $filters): int
    {
        $query = EmployeeFichaProfile::query()
            ->whereNotNull('hire_date')
            ->whereYear('hire_date', $anio)
            ->whereMonth('hire_date', $mes);

        $this->applyFichaDimensionFilters($query, $filters);

        return $query->count();
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countTerminationsInMonth(int $anio, int $mes, array $filters): int
    {
        $query = EmployeeFichaProfile::query()
            ->whereNotNull('termination_date')
            ->whereYear('termination_date', $anio)
            ->whereMonth('termination_date', $mes);

        $this->applyFichaDimensionFilters($query, $filters);

        return $query->count();
    }

    /**
     * @return array{pct: float, display: string}
     */
    private function buildRotacion(int $desvinculaciones, int $activosActual, int $activosAnterior): array
    {
        $denom = ($activosActual + $activosAnterior) / 2;

        if ($denom <= 0) {
            return [
                'pct' => 0.0,
                'display' => '—',
            ];
        }

        $pct = round(($desvinculaciones / $denom) * 100, 1);

        return [
            'pct' => $pct,
            'display' => number_format($pct, 1, '.', '').'%',
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countPsicofisicosVigente(array $filters): int
    {
        return $this->mtSt04BaseQuery($filters)
            ->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VIGENTE)
            ->count();
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function countAcreditacionesAcreditado(array $filters): int
    {
        return $this->acreditacionBaseQuery($filters)
            ->where('acreditacion_acreditados.estado', AcreditacionAcreditado::ESTADO_ACREDITADO)
            ->count();
    }

    private function countClienteInterno(int $anio, int $mes): int
    {
        return ClienteInternoSolicitud::query()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->count();
    }

    private function countFormacion(int $anio, int $mes): int
    {
        return FormacionRegistro::query()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->count();
    }

    /**
     * KPI Archivo = ítems del historial (cédulas/documentos), no cabeceras de consulta.
     * Año por created_at; mes por month_number (mismo criterio que el historial de Archivo).
     */
    private function countArchivoConsultas(int $anio, int $mes): int
    {
        return EmployeeArchiveConsultationItem::query()
            ->whereYear('created_at', $anio)
            ->where('month_number', $mes)
            ->count();
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartEvolucionPersonal(int $anio, array $filters): array
    {
        $labels = [];
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $labels[] = self::MONTH_LABELS[$month];
            $eom = $this->endOfMonth($anio, $month);
            $data[] = $this->countActivosEom($eom, $filters);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{labels: list<string>, ingresos: list<int>, desvinculaciones: list<int>}
     */
    private function chartIngresosVsDesvinculaciones(int $anio, array $filters): array
    {
        $labels = [];
        $ingresos = [];
        $desvinculaciones = [];

        for ($month = 1; $month <= 12; $month++) {
            $labels[] = self::MONTH_LABELS[$month];
            $ingresos[] = $this->countHiresInMonth($anio, $month, $filters);
            $desvinculaciones[] = $this->countTerminationsInMonth($anio, $month, $filters);
        }

        return [
            'labels' => $labels,
            'ingresos' => $ingresos,
            'desvinculaciones' => $desvinculaciones,
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartEvolucionFormacion(int $anio): array
    {
        $counts = FormacionRegistro::query()
            ->where('anio', $anio)
            ->select('mes', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mes')
            ->pluck('aggregate', 'mes');

        $labels = [];
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $labels[] = self::MONTH_LABELS[$month];
            $data[] = (int) ($counts[$month] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Activos EOM agrupados por concepto/área (cargos sin mapa → OTROS). Orden por volumen.
     *
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartPersonalPorConcepto(Carbon $eom, array $filters): array
    {
        $rows = $this->activosEomQuery($eom, $filters)
            ->selectRaw("COALESCE(NULLIF(TRIM(position_name), ''), '') as cargo_label, COUNT(*) as aggregate")
            ->groupByRaw("COALESCE(NULLIF(TRIM(position_name), ''), '')")
            ->get();

        $map = app(GhAreaDashboardConceptoService::class)->positionKeyToConceptoName();
        $buckets = [];

        foreach ($rows as $row) {
            $label = trim((string) $row->cargo_label);
            $count = (int) $row->aggregate;
            if ($count <= 0) {
                continue;
            }

            $concepto = $label === ''
                ? GhAreaDashboardConceptoService::OTROS_LABEL
                : ($map[GhAreaDashboardConceptoCargo::normalizeKey($label)] ?? GhAreaDashboardConceptoService::OTROS_LABEL);

            $buckets[$concepto] = ($buckets[$concepto] ?? 0) + $count;
        }

        arsort($buckets, SORT_NUMERIC);

        return [
            'labels' => array_keys($buckets),
            'data' => array_map(static fn ($v): int => (int) $v, array_values($buckets)),
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartEstadoPsicofisicos(array $filters): array
    {
        $base = $this->mtSt04BaseQuery($filters);

        return [
            'labels' => [
                MtSt04Registro::ESTADO_VIGENTE,
                MtSt04Registro::ESTADO_VENCERA,
                MtSt04Registro::ESTADO_VENCIDO,
            ],
            'data' => [
                (clone $base)->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VIGENTE)->count(),
                (clone $base)->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VENCERA)->count(),
                (clone $base)->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VENCIDO)->count(),
            ],
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartEstadoAcreditacion(array $filters): array
    {
        $base = $this->acreditacionBaseQuery($filters);
        $estados = [
            AcreditacionAcreditado::ESTADO_ACREDITADO,
            AcreditacionAcreditado::ESTADO_EN_PROCESO,
            AcreditacionAcreditado::ESTADO_POR_VENCER,
        ];

        $data = [];
        foreach ($estados as $estado) {
            $data[] = (clone $base)
                ->where('acreditacion_acreditados.estado', $estado)
                ->count();
        }

        return [
            'labels' => ['ACREDITADO', 'EN PROCESO', 'POR VENCER'],
            'data' => $data,
        ];
    }

    /**
     * Pie de motivos sobre ítems del historial (misma base que el KPI Archivo).
     * Cada cédula suma 1 a cada motivo de su consulta (multi-motivo).
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    private function chartConsultasArchivo(int $anio, int $mes): array
    {
        /** @var array<string, string> $typeLabels */
        $typeLabels = config('employee_ficha.archive_consultation_types', []);

        /** @var array<string, int> $counts */
        $counts = [];
        foreach (array_keys($typeLabels) as $key) {
            $counts[$key] = 0;
        }

        EmployeeArchiveConsultationItem::query()
            ->with(['consultation:id,consultation_types'])
            ->whereYear('created_at', $anio)
            ->where('month_number', $mes)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$counts): void {
                foreach ($rows as $row) {
                    $types = $row->consultation?->consultation_types ?? [];
                    if (! is_array($types) || $types === []) {
                        continue;
                    }
                    foreach ($types as $typeKey) {
                        $key = (string) $typeKey;
                        if (! array_key_exists($key, $counts)) {
                            $counts[$key] = 0;
                        }
                        $counts[$key]++;
                    }
                }
            });

        $labels = [];
        $data = [];
        foreach ($counts as $key => $total) {
            if ($total <= 0) {
                continue;
            }
            $labels[] = $typeLabels[$key] ?? $key;
            $data[] = $total;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return Builder<MtSt04Registro>
     */
    private function mtSt04BaseQuery(array $filters): Builder
    {
        $query = MtSt04Registro::query()
            ->leftJoin(
                'employee_ficha_profiles as ficha',
                'ficha.document_number',
                '=',
                'mt_st_04_registros.document_number',
            );

        if ($this->hasFichaDimensionFilters($filters)) {
            $this->applyFichaDimensionFiltersOnAlias($query, $filters, 'ficha');
        }

        return $query;
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     * @return Builder<AcreditacionAcreditado>
     */
    private function acreditacionBaseQuery(array $filters): Builder
    {
        $query = AcreditacionAcreditado::query()
            ->leftJoin(
                'employee_ficha_profiles as ficha',
                'ficha.document_number',
                '=',
                'acreditacion_acreditados.document_number',
            );

        if ($this->hasFichaDimensionFilters($filters)) {
            $this->applyFichaDimensionFiltersOnAlias($query, $filters, 'ficha');
        }

        return $query;
    }

    /**
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function hasFichaDimensionFilters(array $filters): bool
    {
        return $filters['ciudad'] !== ''
            || $filters['cargo'] !== ''
            || $filters['cliente'] !== '';
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function applyFichaDimensionFilters(Builder $query, array $filters): void
    {
        if ($filters['ciudad'] !== '') {
            $query->whereRaw(EmployeeFichaProfile::displayCitySql().' = ?', [$filters['ciudad']]);
        }

        if ($filters['cargo'] !== '') {
            $query->where('position_name', $filters['cargo']);
        }

        if ($filters['cliente'] !== '') {
            $query->whereRaw(self::displayClientSql().' = ?', [$filters['cliente']]);
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{ciudad: string, cargo: string, cliente: string}  $filters
     */
    private function applyFichaDimensionFiltersOnAlias(Builder $query, array $filters, string $alias): void
    {
        if ($filters['ciudad'] !== '') {
            $query->whereRaw(EmployeeFichaProfile::displayCitySql($alias).' = ?', [$filters['ciudad']]);
        }

        if ($filters['cargo'] !== '') {
            $query->where("{$alias}.position_name", $filters['cargo']);
        }

        if ($filters['cliente'] !== '') {
            $query->whereRaw(self::displayClientSql($alias).' = ?', [$filters['cliente']]);
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function yearSelectOptions(?int $selectedAnio = null): array
    {
        $current = (int) now('America/Bogota')->year;
        $years = range($current, $current - 5);

        $fromHire = EmployeeFichaProfile::query()
            ->whereNotNull('hire_date')
            ->selectRaw($this->yearSelectRaw('hire_date'))
            ->distinct()
            ->pluck('anio')
            ->map(static fn ($y): int => (int) $y)
            ->filter(static fn (int $y): bool => $y >= 2000 && $y <= 2100)
            ->all();

        $years = array_values(array_unique(array_merge($years, $fromHire)));

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

    private function yearSelectRaw(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "CAST(strftime('%Y', {$column}) AS INTEGER) as anio";
        }

        return "YEAR({$column}) as anio";
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
        return EmployeeFichaProfile::query()
            ->select('position_name')
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
        $clientSql = self::displayClientSql();

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
