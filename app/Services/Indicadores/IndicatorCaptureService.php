<?php

namespace App\Services\Indicadores;

use App\Models\Improvement;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IndicatorCaptureService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly YearRangeService $yearRangeService,
        private readonly IndicatorMetricCalculator $calculator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildShowContext(Indicator $indicator, int $year, int $month, User $user): array
    {
        $months = config('indicators.months');
        $years = $this->yearRangeService->years();

        $period = Period::query()->firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['status' => Period::STATUS_OPEN]
        );

        $defaultForm = $this->calculator->defaultForm($indicator->code);
        $form = $defaultForm;
        $captureId = null;
        $improvementId = null;
        $analysisText = '';
        $improvement = [
            'analysis' => '',
            'action_taken' => '',
            'action_defined' => '',
            'improvement_required' => '',
        ];

        $capture = IndicatorCapture::query()->where([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'period_id' => $period->id,
        ])->first();

        if ($capture) {
            $captureId = $capture->id;
            $form = array_merge($defaultForm, $capture->input_data ?? []);
            $analysisText = $capture->analysis_text ?? '';
            $improvementModel = Improvement::query()->where('indicator_capture_id', $capture->id)->first();
            $improvementId = $improvementModel?->id;
            $improvement = [
                'analysis' => $improvementModel?->analysis ?? '',
                'action_taken' => $improvementModel?->action_taken ?? '',
                'action_defined' => $improvementModel?->action_defined ?? '',
                'improvement_required' => $improvementModel?->improvement_required ?? '',
            ];
        }

        $form = $this->normalizePostedForm($indicator->code, $form);
        $metrics = $this->calculator->calculate($indicator, $form);
        [$sheetDenominatorLabel, $sheetNumeratorLabel] = $this->calculator->sheetLabels($indicator);

        $sheet = $this->buildSheetData($indicator, $user->id, $year, $sheetDenominatorLabel, $sheetNumeratorLabel);
        $ftOp03 = $indicator->code === 'FT-OP-03'
            ? $this->buildFtOp03Data($indicator, $user->id, $year)
            : [
                'financeRows' => [],
                'incidentRows' => [],
                'quarterlyTables' => [],
                'financeChartPayload' => [],
                'incidentChartPayload' => [],
                'quarterChartPayload' => [],
            ];

        return [
            'indicator' => $indicator,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'selectedUser' => $user,
            'captureUser' => $user,
            'captureUserName' => $user->name,
            'months' => $months,
            'years' => $years,
            'periodId' => $period->id,
            'isPeriodClosed' => $period->isClosed(),
            'form' => $form,
            'captureId' => $captureId,
            'improvementId' => $improvementId,
            'analysisText' => $analysisText,
            'improvementAnalysis' => $improvement['analysis'],
            'improvementActionTaken' => $improvement['action_taken'],
            'improvementActionDefined' => $improvement['action_defined'],
            'improvementRequired' => $improvement['improvement_required'],
            'resultPercentage' => (float) $metrics['result_percentage'],
            'numerator' => (float) $metrics['numerator'],
            'denominator' => (float) $metrics['denominator'],
            'complies' => (bool) $metrics['complies'],
            'semaforo' => $metrics['complies'] ? 'VERDE' : 'ROJO',
            'metricErrors' => $metrics['errors'],
            'sheetRows' => $sheet['sheetRows'],
            'chartPayload' => $sheet['chartPayload'],
            'sheetDenominatorLabel' => $sheetDenominatorLabel,
            'sheetNumeratorLabel' => $sheetNumeratorLabel,
            'fieldsView' => $this->calculator->fieldsPartial($indicator->code),
            'clientFormula' => $this->calculator->clientFormula($indicator->code, $indicator),
            'siniestroOptions' => $this->calculator->siniestroOptions(),
            'financeRows' => $ftOp03['financeRows'],
            'incidentRows' => $ftOp03['incidentRows'],
            'quarterlyTables' => $ftOp03['quarterlyTables'],
            'financeChartPayload' => $ftOp03['financeChartPayload'],
            'incidentChartPayload' => $ftOp03['incidentChartPayload'],
            'quarterChartPayload' => $ftOp03['quarterChartPayload'],
            'openImprovementModal' => (bool) old('_open_improvement_modal', false),
            'openClassificationModal' => (bool) old('_open_classification_modal', false),
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @param  array{analysis: string, action_taken: string, action_defined: string, improvement_required?: string|null}  $improvement
     */
    public function save(
        Indicator $indicator,
        int $year,
        int $month,
        array $form,
        array $improvement,
        User $user,
    ): IndicatorCapture {
        $period = Period::query()->firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['status' => Period::STATUS_OPEN]
        );

        if ($period->isClosed()) {
            throw ValidationException::withMessages(['period' => 'Periodo cerrado']);
        }

        $form = $this->normalizePostedForm($indicator->code, $form);
        $metrics = $this->calculator->calculate($indicator, $form);

        if (! empty($metrics['errors'])) {
            throw ValidationException::withMessages(['form' => implode(' | ', $metrics['errors'])]);
        }

        $this->assertImprovementFields($metrics['complies'], $improvement);

        $analysisText = $this->buildImprovementBlock($improvement, (bool) $metrics['complies']);

        $existing = IndicatorCapture::query()->where([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'period_id' => $period->id,
        ])->first();

        $payload = [
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'period_id' => $period->id,
            'input_data' => $form,
            'numerator' => $metrics['numerator'],
            'denominator' => $metrics['denominator'],
            'result_percentage' => $metrics['result_percentage'],
            'complies' => $metrics['complies'],
            'analysis_text' => $analysisText,
            'updated_by_user_id' => $user->id,
        ];

        if ($existing) {
            $before = $existing->toArray();
            $existing->update($payload);
            $capture = $existing->fresh();

            $this->auditLogService->logModelChange(
                eventType: 'indicator_capture',
                action: 'update',
                model: $capture,
                before: $before,
                after: $capture->toArray(),
                reason: 'Actualizacion captura mensual'
            );
        } else {
            $payload['created_by_user_id'] = $user->id;
            $capture = IndicatorCapture::query()->create($payload);

            $this->auditLogService->logModelChange(
                eventType: 'indicator_capture',
                action: 'create',
                model: $capture,
                before: null,
                after: $capture->toArray(),
                reason: 'Creacion captura mensual'
            );
        }

        $this->persistImprovement(
            capture: $capture,
            indicator: $indicator,
            userId: $user->id,
            periodId: $period->id,
            improvement: $improvement,
            analysisText: $analysisText,
            complies: (bool) $metrics['complies'],
            user: $user,
        );

        return $capture;
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public function normalizePostedForm(string $code, array $form): array
    {
        $form = array_merge($this->calculator->defaultForm($code), $form);

        if ($code === 'FT-OP-03') {
            $totalSiniestros = (float) ($form['total_siniestros'] ?? 0);
            $rows = collect($form['clasificacion_por_tipo'] ?? [])
                ->map(fn ($row) => [
                    'tipo' => trim((string) ($row['tipo'] ?? '')),
                    'cantidad' => $row['cantidad'] === '' || $row['cantidad'] === null ? null : (float) $row['cantidad'],
                ])
                ->filter(fn ($row) => $row['tipo'] !== '')
                ->values()
                ->all();

            if ($totalSiniestros < 1) {
                $form['clasificacion_por_tipo'] = [['tipo' => '', 'cantidad' => null]];
            } else {
                $form['clasificacion_por_tipo'] = $rows !== []
                    ? $rows
                    : [['tipo' => '', 'cantidad' => null]];
            }
        }

        return $form;
    }

    /**
     * @param  array{analysis: string, action_taken: string, action_defined: string, improvement_required?: string|null}  $improvement
     */
    private function assertImprovementFields(bool $complies, array $improvement): void
    {
        $messages = [];

        if (trim((string) ($improvement['analysis'] ?? '')) === '') {
            $messages['improvement.analysis'] = 'El campo analisis es obligatorio.';
        }
        if (trim((string) ($improvement['action_taken'] ?? '')) === '') {
            $messages['improvement.action_taken'] = 'El campo accion tomada es obligatorio.';
        }
        if (trim((string) ($improvement['action_defined'] ?? '')) === '') {
            $messages['improvement.action_defined'] = 'El campo accion definida es obligatorio.';
        }
        if (! $complies && trim((string) ($improvement['improvement_required'] ?? '')) === '') {
            $messages['improvement.improvement_required'] = 'Debe agregar mejora es obligatorio cuando no se cumple la meta.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array{analysis: string, action_taken: string, action_defined: string, improvement_required?: string|null}  $improvement
     */
    private function buildImprovementBlock(array $improvement, bool $complies): string
    {
        $block = "Analisis de resultados:\n".
            'Analisis: '.trim((string) ($improvement['analysis'] ?? ''))."\n".
            'Accion tomada: '.trim((string) ($improvement['action_taken'] ?? ''))."\n".
            'Accion definida: '.trim((string) ($improvement['action_defined'] ?? ''));

        if (! $complies && trim((string) ($improvement['improvement_required'] ?? '')) !== '') {
            $block .= "\n".'Debe agregar mejora: '.trim((string) $improvement['improvement_required']);
        }

        return $block;
    }

    /**
     * @param  array{analysis: string, action_taken: string, action_defined: string, improvement_required?: string|null}  $improvement
     */
    private function persistImprovement(
        IndicatorCapture $capture,
        Indicator $indicator,
        int $userId,
        int $periodId,
        array $improvement,
        string $analysisText,
        bool $complies,
        User $user,
    ): void {
        $existing = Improvement::query()->where('indicator_capture_id', $capture->id)->first();
        $payload = [
            'indicator_capture_id' => $capture->id,
            'indicator_id' => $indicator->id,
            'user_id' => $userId,
            'period_id' => $periodId,
            'analysis' => trim((string) ($improvement['analysis'] ?? '')),
            'action_taken' => trim((string) ($improvement['action_taken'] ?? '')),
            'action_defined' => trim((string) ($improvement['action_defined'] ?? '')),
            'improvement_required' => $complies ? null : trim((string) ($improvement['improvement_required'] ?? '')),
            'integrated_analysis_block' => $analysisText,
            'created_by_user_id' => $user->id,
        ];

        if ($existing) {
            $before = $existing->toArray();
            $existing->update($payload);
            $this->auditLogService->logModelChange(
                eventType: 'improvement',
                action: 'update',
                model: $existing,
                before: $before,
                after: $existing->fresh()->toArray(),
                reason: 'Actualizacion analisis mensual'
            );

            return;
        }

        $model = Improvement::query()->create($payload);
        $this->auditLogService->logModelChange(
            eventType: 'improvement',
            action: 'create',
            model: $model,
            before: null,
            after: $model->toArray(),
            reason: 'Creacion analisis mensual'
        );
    }

    /**
     * @return array{sheetRows: list<array<string, mixed>>, chartPayload: array<string, mixed>}
     */
    private function buildSheetData(
        Indicator $indicator,
        int $userId,
        int $year,
        string $sheetDenominatorLabel,
        string $sheetNumeratorLabel,
    ): array {
        $monthNames = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
        ];

        if ($userId <= 0) {
            return ['sheetRows' => [], 'chartPayload' => []];
        }

        $periods = Period::query()
            ->where('year', $year)
            ->whereBetween('month', [1, 12])
            ->get(['id', 'month'])
            ->keyBy('month');

        $captures = IndicatorCapture::query()
            ->where('indicator_id', $indicator->id)
            ->where('user_id', $userId)
            ->whereIn('period_id', $periods->pluck('id'))
            ->get(['id', 'period_id', 'numerator', 'denominator', 'result_percentage', 'complies', 'analysis_text'])
            ->keyBy(function (IndicatorCapture $capture) use ($periods): int {
                $period = $periods->firstWhere('id', $capture->period_id);

                return (int) ($period?->month ?? 0);
            });

        $rows = [];
        $denominators = [];
        $numerators = [];
        $percentages = [];

        for ($m = 1; $m <= 12; $m++) {
            $capture = $captures->get($m);
            $analysis = trim((string) ($capture?->analysis_text ?? ''));
            $analysis = preg_replace('/\n{3,}/', "\n\n", $analysis) ?? $analysis;

            $denominator = (float) ($capture?->denominator ?? 0);
            $numerator = (float) ($capture?->numerator ?? 0);
            $result = (float) ($capture?->result_percentage ?? 0);

            $rows[] = [
                'month_number' => $m,
                'month' => $monthNames[$m],
                'denominator' => $denominator,
                'numerator' => $numerator,
                'result_percentage' => $result,
                'analysis' => $analysis,
                'has_capture' => (bool) $capture,
                'complies' => (bool) ($capture?->complies ?? false),
                'improvement' => $capture ? ! (bool) ($capture->complies ?? false) : false,
            ];

            $denominators[] = $denominator;
            $numerators[] = $numerator;
            $percentages[] = $result;
        }

        return [
            'sheetRows' => $rows,
            'chartPayload' => [
                'months' => array_values($monthNames),
                'denominator' => $denominators,
                'numerator' => $numerators,
                'result_percentage' => $percentages,
                'meta' => array_fill(0, 12, (float) $indicator->target_value),
                'denominator_label' => $sheetDenominatorLabel,
                'numerator_label' => $sheetNumeratorLabel,
                'title' => 'Nivel de cumplimiento '.$indicator->name.' '.$year,
                'year' => $year,
            ],
        ];
    }

    /**
     * @return array{
     *     financeRows: array<string, mixed>,
     *     incidentRows: array<string, mixed>,
     *     quarterlyTables: array<int, mixed>,
     *     financeChartPayload: array<string, mixed>,
     *     incidentChartPayload: array<string, mixed>,
     *     quarterChartPayload: array<int, mixed>
     * }
     */
    private function buildFtOp03Data(Indicator $indicator, int $userId, int $year): array
    {
        $monthNames = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
        ];

        $empty = [
            'financeRows' => [],
            'incidentRows' => [],
            'quarterlyTables' => [],
            'financeChartPayload' => [],
            'incidentChartPayload' => [],
            'quarterChartPayload' => [],
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $periods = Period::query()
            ->where('year', $year)
            ->whereBetween('month', [1, 12])
            ->get(['id', 'month'])
            ->keyBy('month');

        $captures = IndicatorCapture::query()
            ->where('indicator_id', $indicator->id)
            ->where('user_id', $userId)
            ->whereIn('period_id', $periods->pluck('id'))
            ->get(['id', 'period_id', 'input_data'])
            ->keyBy(function (IndicatorCapture $capture) use ($periods): int {
                $period = $periods->firstWhere('id', $capture->period_id);

                return (int) ($period?->month ?? 0);
            });

        $facturacion = [];
        $valorPagado = [];
        $pctCumplimiento = [];
        $clientes = [];
        $siniestros = [];
        $pctSiniestros = [];
        $quarterTypeTotals = [1 => [], 2 => [], 3 => [], 4 => []];

        for ($m = 1; $m <= 12; $m++) {
            $input = (array) (($captures->get($m)?->input_data) ?? []);
            $f = (float) ($input['facturacion_mensual'] ?? 0);
            $v = (float) ($input['valor_pagado_siniestros'] ?? 0);
            $c = (float) ($input['total_servicios'] ?? 0);
            $s = (float) ($input['total_siniestros'] ?? 0);
            $p1 = $f > 0 ? round(($v / $f) * 100, 2) : 0.0;
            $p2 = $c > 0 ? round(($s / $c) * 100, 2) : 0.0;

            $facturacion[$m] = $f;
            $valorPagado[$m] = $v;
            $pctCumplimiento[$m] = $p1;
            $clientes[$m] = $c;
            $siniestros[$m] = $s;
            $pctSiniestros[$m] = $p2;

            $quarter = (int) ceil($m / 3);
            foreach ((array) ($input['clasificacion_por_tipo'] ?? []) as $row) {
                $type = trim((string) ($row['tipo'] ?? ''));
                if ($type === '') {
                    continue;
                }
                $qty = (float) ($row['cantidad'] ?? 0);
                $quarterTypeTotals[$quarter][$type] = ($quarterTypeTotals[$quarter][$type] ?? 0) + $qty;
            }
        }

        $defaultTypes = $this->calculator->siniestroOptions();
        $allTypes = collect($defaultTypes);
        foreach ($quarterTypeTotals as $types) {
            foreach (array_keys($types) as $type) {
                if (! $allTypes->contains($type)) {
                    $allTypes->push($type);
                }
            }
        }

        $typeList = $allTypes->take(5)->values()->all();
        $quarterlyTables = [];
        $quarterChartPayload = [];

        for ($q = 1; $q <= 4; $q++) {
            $rows = [];
            $sum = 0.0;
            foreach ($typeList as $type) {
                $qty = (float) ($quarterTypeTotals[$q][$type] ?? 0);
                $rows[] = ['type' => $type, 'qty' => $qty];
                $sum += $qty;
            }
            foreach ($rows as &$row) {
                $row['pct'] = $sum > 0 ? round(($row['qty'] / $sum) * 100, 2) : 0.0;
            }
            unset($row);

            $quarterlyTables[$q] = [
                'rows' => $rows,
                'total_qty' => $sum,
            ];
            $quarterChartPayload[$q] = [
                'title' => match ($q) {
                    1 => 'CARACTERIZACION Y TENDENCIA PRIMER TRIMESTRE',
                    2 => 'CARACTERIZACION Y TENDENCIA SEGUNDO TRIMESTRE',
                    3 => 'CARACTERIZACION Y TENDENCIA TERCER TRIMESTRE',
                    default => 'CARACTERIZACION Y TENDENCIA CUARTO TRIMESTRE',
                },
                'data' => array_map(fn ($r) => ['name' => strtoupper($r['type']), 'value' => $r['qty']], $rows),
            ];
        }

        $sumFact = array_sum($facturacion);
        $sumPago = array_sum($valorPagado);
        $sumPct = $sumFact > 0 ? round(($sumPago / $sumFact) * 100, 2) : 0.0;

        $sumCli = array_sum($clientes);
        $sumSin = array_sum($siniestros);
        $sumSinPct = $sumCli > 0 ? round(($sumSin / $sumCli) * 100, 2) : 0.0;

        return [
            'financeRows' => [
                'facturacion' => $facturacion,
                'pagado' => $valorPagado,
                'cumplimiento' => $pctCumplimiento,
                'totals' => ['facturacion' => $sumFact, 'pagado' => $sumPago, 'cumplimiento' => $sumPct],
            ],
            'incidentRows' => [
                'clientes' => $clientes,
                'siniestros' => $siniestros,
                'porcentaje' => $pctSiniestros,
                'totals' => ['clientes' => $sumCli, 'siniestros' => $sumSin, 'porcentaje' => $sumSinPct],
            ],
            'quarterlyTables' => $quarterlyTables,
            'financeChartPayload' => [
                'months' => array_values($monthNames),
                'facturacion' => array_values($facturacion),
                'pagado' => array_values($valorPagado),
                'cumplimiento' => array_values($pctCumplimiento),
            ],
            'incidentChartPayload' => [
                'months' => array_values($monthNames),
                'clientes' => array_values($clientes),
                'siniestros' => array_values($siniestros),
                'porcentaje' => array_values($pctSiniestros),
            ],
            'quarterChartPayload' => $quarterChartPayload,
        ];
    }
}
