<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningCapture;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use DomainException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PlaneacionConsolidadoService
{
    /**
     * @var array<int, string>
     */
    public const MONTH_ABBREV = [
        1 => 'ENE',
        2 => 'FEB',
        3 => 'MAR',
        4 => 'ABR',
        5 => 'MAY',
        6 => 'JUN',
        7 => 'JUL',
        8 => 'AGO',
        9 => 'SEP',
        10 => 'OCT',
        11 => 'NOV',
        12 => 'DIC',
    ];

    public function __construct(
        private readonly PlaneacionPeriodService $periods,
        private readonly FormulaEvaluator $evaluator,
        private readonly PlaneacionCaptureService $captures,
        private readonly PlaneacionEstrategicaAccessService $access,
    ) {}

    /**
     * Ficha anual tipo FT-OP-01: filas por campo, consolidado suma + recálculo.
     *
     * @return array<string, mixed>
     */
    public function ficha(
        QualityPlanningIndicator $indicator,
        User $viewer,
        int $year,
        int $month,
        ?int $capturadorId,
        bool $forceOwn = false,
        ?int $consultVersionId = null,
    ): array {
        $indicator->loadMissing(['versions', 'currentPublishedVersion', 'assignees.user']);

        $filter = $this->resolveCapturadorFilter($indicator, $viewer, $capturadorId, $forceOwn);
        $selectedPeriod = $this->periods->versionForCapture($indicator, $year, $month);
        $pinnedVersion = $this->resolvePinnedVersion($indicator, $consultVersionId);
        $headerVersion = $pinnedVersion ?? $this->headerVersion($indicator, $selectedPeriod->version);
        $fieldRows = $this->fieldRows($headerVersion);

        $captures = $this->yearCaptures($indicator, $year, $filter['capturador_id']);
        $isTodos = $filter['capturador_id'] === null;

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $period = $this->periods->versionForCapture($indicator, $year, $m);
            [$rowPeriod, $monthCaptures] = $this->monthRowSource(
                $captures,
                $m,
                $period,
                $pinnedVersion,
            );

            $months[] = $this->buildMonthRow(
                $m,
                $rowPeriod,
                $monthCaptures,
                $fieldRows,
                $isTodos,
            );
        }

        $formulaLabels = [];
        foreach ($fieldRows as $field) {
            $formulaLabels[$field['key']] = $field['label'];
        }

        return [
            'year' => $year,
            'month' => $month,
            'period_label' => mb_convert_case($this->periods->monthLabel($month), MB_CASE_TITLE, 'UTF-8').' de '.$year,
            'version_number' => $headerVersion?->version_number ?? 1,
            'name' => $headerVersion?->name ?? $indicator->code,
            'objective' => $headerVersion?->objective ?: 'Medir el grado de cumplimiento del indicador.',
            'process' => $headerVersion?->processLabel() ?? '—',
            'unit_label' => $headerVersion?->unitLabel() ?? 'Porcentaje',
            'unit_suffix' => ($headerVersion?->unit === QualityPlanningIndicatorVersion::UNIT_PERCENTAGE) ? '%' : '',
            'frequency' => $headerVersion?->frequencyLabel() ?? 'Mensual',
            'tendency' => $this->tendency($headerVersion?->target_operator ?? '>='),
            'target_operator' => $headerVersion?->target_operator ?? '>=',
            'target_value' => $headerVersion !== null ? (float) $headerVersion->target_value : null,
            'critical_value' => $headerVersion?->critical_value !== null ? (float) $headerVersion->critical_value : null,
            'formula_display' => $headerVersion instanceof QualityPlanningIndicatorVersion
                ? $this->evaluator->displayWithLabels((string) $headerVersion->formula, $formulaLabels)
                : '',
            'insumos' => $headerVersion?->insumos ?: '—',
            'responsibilities' => $this->responsibilities($indicator, $filter['show_filter'], $headerVersion),
            'show_capturador_filter' => $filter['show_filter'],
            'capturador_options' => $filter['options'],
            'capturador_id' => $filter['capturador_id'],
            'fields' => $fieldRows,
            'months' => $months,
            'chart' => $this->chartPayload($headerVersion, $year, $fieldRows, $months),
            'consult_version_id' => $pinnedVersion?->id,
        ];
    }

    /**
     * Consolidado de un mes: un capturador o suma de campos + recálculo.
     *
     * @return array<string, mixed>
     */
    public function monthSnapshot(
        QualityPlanningIndicator $indicator,
        int $year,
        int $month,
        ?int $capturadorId = null,
    ): array {
        $indicator->loadMissing(['versions', 'currentPublishedVersion']);
        $period = $this->periods->versionForCapture($indicator, $year, $month);
        $fieldRows = $this->fieldRows($this->headerVersion($indicator, $period->version));

        $captures = collect();
        if ($period->version instanceof QualityPlanningIndicatorVersion) {
            $query = QualityPlanningCapture::query()
                ->where('indicator_version_id', $period->version->id)
                ->where('year', $year)
                ->where('month', $month);

            if ($capturadorId !== null) {
                $query->where('user_id', $capturadorId);
            }

            $captures = $query->get();
        }

        $row = $this->buildMonthRow($month, $period, $captures, $fieldRows, $capturadorId === null);

        return [
            'version' => $period->version,
            'closed' => $period->closed,
            'has_data' => $row['has_data'],
            'fields' => $row['field_values'],
            'result' => $row['result'],
            'complies' => $row['complies'],
            'is_critical' => $row['is_critical'],
            'analysis' => $row['analysis'],
            'improvement' => $row['improvement'],
            'target' => $row['target_value'],
            'operator' => $row['target_operator'],
            'critical_value' => $row['critical_value'],
            'name' => $period->version?->name ?? $indicator->currentPublishedVersion?->name ?? $indicator->code,
            'capture_count' => $row['capture_count'],
            'captured_user_ids' => $row['captured_user_ids'],
        ];
    }

    /**
     * Capturadores activos (can_capture) visibles según el perfil del consultante.
     *
     * @return Collection<int, User>
     */
    public function visibleCapturers(QualityPlanningIndicator $indicator, User $viewer): Collection
    {
        $indicator->loadMissing('assignees.user');

        if (! $this->access->canEditIndicadores($viewer) && ! $this->access->canConsultIndicator($viewer, $indicator)) {
            return collect();
        }

        return $this->capturersOf($indicator);
    }

    /**
     * @return array{show_filter: bool, capturador_id: int|null, options: list<array{value: string, label: string}>}
     */
    public function resolveCapturadorFilter(
        QualityPlanningIndicator $indicator,
        User $viewer,
        ?int $requestedId,
        bool $forceOwn = false,
    ): array {
        if ($forceOwn || (! $this->access->canEditIndicadores($viewer) && ! $this->access->canConsultIndicator($viewer, $indicator))) {
            return [
                'show_filter' => false,
                'capturador_id' => (int) $viewer->id,
                'options' => [],
            ];
        }

        $capturers = $this->capturersOf($indicator);
        $allowedIds = $capturers->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $selected = $requestedId;
        if ($selected !== null && ! in_array($selected, $allowedIds, true)) {
            $selected = null;
        }

        $options = [
            ['value' => '', 'label' => 'Todos los capturadores'],
        ];
        foreach ($capturers as $user) {
            $options[] = [
                'value' => (string) $user->id,
                'label' => (string) $user->name,
            ];
        }

        return [
            'show_filter' => true,
            'capturador_id' => $selected,
            'options' => $options,
        ];
    }

    public function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_numeric($value)) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    /**
     * @return Collection<int, User>
     */
    private function capturersOf(QualityPlanningIndicator $indicator): Collection
    {
        return $indicator->assignees
            ->filter(function ($row): bool {
                return (bool) $row->can_capture
                    && $row->user instanceof User
                    && (bool) $row->user->is_active;
            })
            ->map(fn ($row): User => $row->user)
            ->sortBy(fn (User $user): string => mb_strtoupper((string) $user->name))
            ->values();
    }

    /**
     * @return Collection<int, Collection<int, QualityPlanningCapture>>
     */
    private function yearCaptures(QualityPlanningIndicator $indicator, int $year, ?int $capturadorId): Collection
    {
        $versionIds = $indicator->versions->pluck('id');
        if ($versionIds->isEmpty()) {
            return collect();
        }

        $query = QualityPlanningCapture::query()
            ->whereIn('indicator_version_id', $versionIds)
            ->where('year', $year);

        if ($capturadorId !== null) {
            $query->where('user_id', $capturadorId);
        }

        return $query->get()->groupBy(fn (QualityPlanningCapture $capture): int => (int) $capture->month);
    }

    private function resolvePinnedVersion(
        QualityPlanningIndicator $indicator,
        ?int $consultVersionId,
    ): ?QualityPlanningIndicatorVersion {
        if ($consultVersionId === null || $consultVersionId < 1) {
            return null;
        }

        $version = $indicator->versions->firstWhere('id', $consultVersionId);

        return $version instanceof QualityPlanningIndicatorVersion ? $version : null;
    }

    /**
     * Capturas del mes: versión que rige el periodo, o una versión consultada a propósito.
     *
     * @param  Collection<int, Collection<int, QualityPlanningCapture>>  $captures
     * @return array{0: PlaneacionCapturePeriod, 1: Collection<int, QualityPlanningCapture>}
     */
    private function monthRowSource(
        Collection $captures,
        int $month,
        PlaneacionCapturePeriod $period,
        ?QualityPlanningIndicatorVersion $pinnedVersion,
    ): array {
        $monthCaptures = collect($captures->get($month) ?? $captures->get((string) $month) ?? []);

        if ($pinnedVersion instanceof QualityPlanningIndicatorVersion) {
            return [
                new PlaneacionCapturePeriod($pinnedVersion, false),
                $monthCaptures
                    ->filter(fn (QualityPlanningCapture $capture): bool => (int) $capture->indicator_version_id === (int) $pinnedVersion->id)
                    ->values(),
            ];
        }

        return [
            $period,
            $monthCaptures
                ->filter(function (QualityPlanningCapture $capture) use ($period): bool {
                    return $period->version instanceof QualityPlanningIndicatorVersion
                        && (int) $capture->indicator_version_id === (int) $period->version->id;
                })
                ->values(),
        ];
    }

    /**
     * @param  Collection<int, QualityPlanningCapture>  $monthCaptures
     * @param  list<array{key: string, label: string, decimals: int}>  $fieldRows
     * @return array<string, mixed>
     */
    private function buildMonthRow(
        int $month,
        PlaneacionCapturePeriod $period,
        Collection $monthCaptures,
        array $fieldRows,
        bool $isTodos,
    ): array {
        $abbrev = self::MONTH_ABBREV[$month] ?? (string) $month;
        $emptyFields = [];
        foreach ($fieldRows as $field) {
            $emptyFields[$field['key']] = null;
        }

        $base = [
            'month' => $month,
            'abbrev' => $abbrev,
            'closed' => $period->closed,
            'has_data' => false,
            'field_values' => $emptyFields,
            'result' => null,
            'complies' => false,
            'is_critical' => false,
            'analysis' => '',
            'improvement' => false,
            'target_operator' => $period->version?->target_operator ?? '',
            'target_value' => $period->version !== null ? (float) $period->version->target_value : null,
            'critical_value' => $period->version?->critical_value !== null ? (float) $period->version->critical_value : null,
            'target_display' => '',
            'critical_display' => '',
            'capture_count' => 0,
            'captured_user_ids' => [],
        ];

        if (! $period->version instanceof QualityPlanningIndicatorVersion) {
            return $base;
        }

        $version = $period->version;
        $unitSuffix = $version->unit === QualityPlanningIndicatorVersion::UNIT_PERCENTAGE ? '%' : '';
        $base['target_display'] = $version->target_operator.' '.$this->formatValue((float) $version->target_value).$unitSuffix;
        $base['critical_display'] = $version->critical_value !== null
            ? 'CRÍTICO '.$this->formatValue((float) $version->critical_value).$unitSuffix
            : '';

        if ($monthCaptures->isEmpty()) {
            return $base;
        }

        $versionKeys = [];
        foreach ($version->fields ?? [] as $field) {
            if (is_array($field) && (string) ($field['key'] ?? '') !== '') {
                $versionKeys[(string) $field['key']] = true;
            }
        }

        $sums = [];
        foreach ($fieldRows as $field) {
            $key = $field['key'];
            if (! isset($versionKeys[$key])) {
                continue;
            }

            $sum = 0.0;
            $found = false;
            foreach ($monthCaptures as $capture) {
                $input = is_array($capture->input_data) ? $capture->input_data : [];
                if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
                    continue;
                }
                $sum += (float) $input[$key];
                $found = true;
            }

            if ($found) {
                $sums[$key] = $sum;
            }
        }

        $single = $monthCaptures->count() === 1 ? $monthCaptures->first() : null;
        $result = $this->evaluateResult($version, $sums, $single);
        $complies = $result !== null && $this->captures->complies($result, $version);
        $isCritical = $this->isCritical($result, $version, $complies);

        $analysis = '—';
        if (! $isTodos && $single instanceof QualityPlanningCapture) {
            $analysis = trim((string) $single->analysis_text);
        }

        return array_merge($base, [
            'has_data' => true,
            'field_values' => array_merge($emptyFields, $sums),
            'result' => $result,
            'complies' => $complies,
            'is_critical' => $isCritical,
            'analysis' => $analysis,
            'improvement' => ! $complies,
            'capture_count' => $monthCaptures->count(),
            'captured_user_ids' => $monthCaptures->pluck('user_id')->map(fn ($id): int => (int) $id)->unique()->values()->all(),
        ]);
    }

    /**
     * @param  array<string, float>  $values
     */
    private function evaluateResult(
        QualityPlanningIndicatorVersion $version,
        array $values,
        ?QualityPlanningCapture $fallback,
    ): ?float {
        try {
            return $this->evaluator->evaluate((string) $version->formula, $values);
        } catch (DomainException|InvalidArgumentException) {
            if ($fallback instanceof QualityPlanningCapture && $fallback->result_value !== null) {
                return (float) $fallback->result_value;
            }

            return null;
        }
    }

    private function isCritical(?float $result, QualityPlanningIndicatorVersion $version, bool $complies): bool
    {
        if ($result === null || $complies || $version->critical_value === null) {
            return ! $complies && $result !== null;
        }

        $critical = (float) $version->critical_value;

        return match ((string) $version->target_operator) {
            '<=', '<' => $result >= $critical,
            default => $result <= $critical,
        };
    }

    private function headerVersion(
        QualityPlanningIndicator $indicator,
        ?QualityPlanningIndicatorVersion $monthVersion,
    ): ?QualityPlanningIndicatorVersion {
        return $monthVersion
            ?? $indicator->currentPublishedVersion
            ?? $indicator->workingVersion();
    }

    /**
     * @return list<array{key: string, label: string, decimals: int}>
     */
    private function fieldRows(?QualityPlanningIndicatorVersion $version): array
    {
        if (! $version instanceof QualityPlanningIndicatorVersion) {
            return [];
        }

        $rows = [];
        foreach ($version->fields ?? [] as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $rows[] = [
                'key' => $key,
                'label' => (string) ($field['label'] ?? $key),
                'decimals' => in_array((int) ($field['decimals'] ?? 2), [0, 2], true) ? (int) ($field['decimals'] ?? 2) : 2,
            ];
        }

        return $rows;
    }

    /**
     * @return array{measurement: string, results: string, measure: string}
     */
    private function responsibilities(
        QualityPlanningIndicator $indicator,
        bool $showTeam,
        ?QualityPlanningIndicatorVersion $version,
    ): array {
        $measure = $version?->medicion ?: '—';

        if (! $showTeam) {
            return [
                'measurement' => 'Calidad',
                'results' => 'Capturador',
                'measure' => $measure,
            ];
        }

        $names = $this->capturersOf($indicator)
            ->map(fn (User $user): string => (string) $user->name)
            ->filter()
            ->implode(', ');

        return [
            'measurement' => 'Calidad',
            'results' => $names !== '' ? $names : 'Capturadores asignados',
            'measure' => $measure,
        ];
    }

    private function tendency(string $operator): string
    {
        return match ($operator) {
            '<=', '<' => 'Decreciente',
            '=', '==' => 'Objetivo exacto',
            default => 'Creciente',
        };
    }

    /**
     * @param  list<array{key: string, label: string, decimals: int}>  $fieldRows
     * @param  list<array<string, mixed>>  $months
     * @return array<string, mixed>
     */
    private function chartPayload(
        ?QualityPlanningIndicatorVersion $version,
        int $year,
        array $fieldRows,
        array $months,
    ): array {
        $categories = [];
        $resultSeries = [];
        $metaSeries = [];
        $fieldSeries = [];
        foreach ($fieldRows as $field) {
            $fieldSeries[$field['key']] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'data' => [],
            ];
        }

        $maxResult = 0.0;
        foreach ($months as $row) {
            $categories[] = $row['abbrev'];
            $resultSeries[] = $row['has_data'] && $row['result'] !== null ? round((float) $row['result'], 2) : null;
            $metaSeries[] = $row['target_value'];
            if ($row['result'] !== null) {
                $maxResult = max($maxResult, (float) $row['result']);
            }
            foreach ($fieldRows as $field) {
                $value = $row['field_values'][$field['key']] ?? null;
                $fieldSeries[$field['key']]['data'][] = $value === null ? null : round((float) $value, 2);
            }
        }

        $yRightMax = (int) max(100, (int) ceil($maxResult > 0 ? $maxResult : 100));

        return [
            'title' => 'Nivel de cumplimiento '.($version?->name ?? '').' '.$year,
            'months' => $categories,
            'result' => $resultSeries,
            'meta' => $metaSeries,
            'fields' => array_values($fieldSeries),
            'y_right_max' => $yRightMax,
        ];
    }
}
