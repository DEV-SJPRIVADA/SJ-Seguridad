<?php

namespace App\Livewire\Indicadores;

use App\Models\Improvement;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\OperationsLeader;
use App\Services\Indicadores\AuditLogService;
use App\Services\Indicadores\YearRangeService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

abstract class BaseIndicatorForm extends Component
{
    public Indicator $indicator;
    public int $selectedYear;
    public int $selectedMonth;
    public ?int $selectedOperationsLeaderId = null;

    public array $operationsLeaders = [];
    public array $months = [];
    public array $years = [];
    public array $form = [];
    public array $trendRows = [];
    public array $metricErrors = [];
    public array $sheetRows = [];
    public array $chartPayload = [];

    public ?int $periodId = null;
    public ?int $captureId = null;
    public ?int $improvementId = null;

    public float $resultPercentage = 0.0;
    public float $numerator = 0.0;
    public float $denominator = 0.0;
    public bool $complies = false;
    public string $semaforo = 'ROJO';
    public string $analysisText = '';
    public bool $isPeriodClosed = false;

    public bool $showImprovementModal = false;
    public string $improvementAnalysis = '';
    public string $improvementActionTaken = '';
    public string $improvementActionDefined = '';
    public string $improvementRequired = '';
    public string $sheetDenominatorLabel = 'TOTAL BASE';
    public string $sheetNumeratorLabel = 'TOTAL CUMPLIDO';

    protected AuditLogService $auditLogService;

    protected string $fieldsView = '';

    abstract protected function defaultForm(): array;
    abstract protected function fieldRules(): array;
    abstract protected function calculateMetrics(array $form): array;

    public function boot(AuditLogService $auditLogService): void
    {
        $this->auditLogService = $auditLogService;
    }

    public function mount(Indicator $indicator): void
    {
        $this->indicator = $indicator;
        $this->form = $this->defaultForm();
        $now = now();
        $yearRange = app(YearRangeService::class);
        $this->years = $yearRange->years();
        $this->months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $requestedYear = (int) request()->integer('year', (int) $now->year);
        $requestedMonth = (int) request()->integer('month', (int) $now->month);
        $requestedOperationsLeaderId = (int) request()->integer('operations_leader_id', 0);

        $this->selectedYear = $yearRange->normalize($requestedYear);
        $this->selectedMonth = array_key_exists($requestedMonth, $this->months) ? $requestedMonth : (int) $now->month;

        $this->loadOperationsLeaders();
        if ($requestedOperationsLeaderId > 0) {
            $allowedOperationsLeaderIds = array_map(static fn (array $leader): int => (int) $leader['id'], $this->operationsLeaders);
            if (in_array($requestedOperationsLeaderId, $allowedOperationsLeaderIds, true)) {
                $this->selectedOperationsLeaderId = $requestedOperationsLeaderId;
            }
        }
        $this->configureSheetLabels();
        $this->loadContext();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadContext();
    }

    public function updatedSelectedMonth(): void
    {
        $this->loadContext();
    }

    public function updatedSelectedOperationsLeaderId(): void
    {
        $this->loadContext();
    }

    public function updatedForm(): void
    {
        $this->computeCurrentMetrics();
    }

    public function updated($property): void
    {
        if (str_starts_with((string) $property, 'form.')) {
            $this->computeCurrentMetrics();
        }
    }

    public function save(): void
    {
        $this->ensureContext();
        $this->validate($this->fieldRules());
        $this->computeCurrentMetrics();
        $this->validateImprovementFields();

        if ($this->isPeriodClosed) {
            throw ValidationException::withMessages(['period' => 'Periodo cerrado']);
        }

        if (! empty($this->metricErrors)) {
            throw ValidationException::withMessages(['form' => implode(' | ', $this->metricErrors)]);
        }

        $this->analysisText = $this->buildImprovementBlock();
        $period = Period::query()->findOrFail($this->periodId);

        $existing = IndicatorCapture::query()->where([
            'indicator_id' => $this->indicator->id,
            'operations_leader_id' => $this->selectedOperationsLeaderId,
            'period_id' => $period->id,
        ])->first();

        $payload = [
            'indicator_id' => $this->indicator->id,
            'operations_leader_id' => $this->selectedOperationsLeaderId,
            'period_id' => $period->id,
            'input_data' => $this->form,
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
            'result_percentage' => $this->resultPercentage,
            'complies' => $this->complies,
            'analysis_text' => $this->analysisText,
            'updated_by_user_id' => auth()->id(),
        ];

        if ($existing) {
            $before = $existing->toArray();
            $existing->update($payload);
            $after = $existing->fresh()->toArray();

            $this->auditLogService->logModelChange(
                eventType: 'indicator_capture',
                action: 'update',
                model: $existing,
                before: $before,
                after: $after,
                reason: 'Actualizacion captura mensual'
            );

            $this->captureId = $existing->id;
            $captureModel = $existing;
        } else {
            $payload['created_by_user_id'] = auth()->id();
            $capture = IndicatorCapture::query()->create($payload);

            $this->auditLogService->logModelChange(
                eventType: 'indicator_capture',
                action: 'create',
                model: $capture,
                before: null,
                after: $capture->toArray(),
                reason: 'Creacion captura mensual'
            );

            $this->captureId = $capture->id;
            $captureModel = $capture;
        }

        $this->persistImprovement($captureModel);
        session()->flash('status', 'Captura guardada correctamente para el mes seleccionado.');
        $this->showImprovementModal = false;
        $this->loadContext();
    }

    public function openImprovementModal(): void
    {
        if ($this->captureId) {
            $improvement = Improvement::query()->where('indicator_capture_id', $this->captureId)->first();
            $this->improvementId = $improvement?->id;
            $this->improvementAnalysis = $improvement?->analysis ?? $this->improvementAnalysis;
            $this->improvementActionTaken = $improvement?->action_taken ?? $this->improvementActionTaken;
            $this->improvementActionDefined = $improvement?->action_defined ?? $this->improvementActionDefined;
            $this->improvementRequired = $improvement?->improvement_required ?? $this->improvementRequired;
        }

        $this->showImprovementModal = true;
    }

    public function closeImprovementModal(): void
    {
        $this->showImprovementModal = false;
    }

    public function saveImprovement(): void
    {
        $this->ensureContext();

        if ($this->isPeriodClosed) {
            throw ValidationException::withMessages(['period' => 'Periodo cerrado']);
        }

        $this->computeCurrentMetrics();
        $this->validateImprovementFields();
        $this->analysisText = $this->buildImprovementBlock();

        if ($this->captureId) {
            $capture = IndicatorCapture::query()->findOrFail($this->captureId);
            $beforeCapture = $capture->toArray();
            $capture->update([
                'analysis_text' => $this->analysisText,
                'updated_by_user_id' => auth()->id(),
            ]);
            $this->auditLogService->logModelChange(
                eventType: 'indicator_capture',
                action: 'update',
                model: $capture,
                before: $beforeCapture,
                after: $capture->fresh()->toArray(),
                reason: 'Actualizacion analisis mensual desde modal'
            );
            $this->persistImprovement($capture);
            session()->flash('status', 'Analisis guardado correctamente.');
        } else {
            session()->flash('status', 'Analisis registrado en memoria. Ahora pulsa Guardar mes.');
        }

        $this->showImprovementModal = false;
    }

    protected function ensureContext(): void
    {
        if (! $this->selectedOperationsLeaderId) {
            throw ValidationException::withMessages(['operations_leader' => 'Debes seleccionar un jefe de operaciones.']);
        }
    }

    protected function loadOperationsLeaders(): void
    {
        $this->operationsLeaders = OperationsLeader::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();

        if (! $this->selectedOperationsLeaderId && ! empty($this->operationsLeaders)) {
            $this->selectedOperationsLeaderId = (int) $this->operationsLeaders[0]['id'];
        }
    }

    protected function loadContext(): void
    {
        if (! $this->selectedOperationsLeaderId) {
            return;
        }

        $this->assertZoneAccess();
        $period = Period::query()->firstOrCreate(
            ['year' => $this->selectedYear, 'month' => $this->selectedMonth],
            ['status' => Period::STATUS_OPEN]
        );

        $this->periodId = $period->id;
        $this->isPeriodClosed = $period->isClosed();

        $capture = IndicatorCapture::query()->where([
            'indicator_id' => $this->indicator->id,
            'operations_leader_id' => $this->selectedOperationsLeaderId,
            'period_id' => $period->id,
        ])->first();

        if ($capture) {
            $this->captureId = $capture->id;
            $this->form = array_merge($this->defaultForm(), $capture->input_data ?? []);
            $this->analysisText = $capture->analysis_text ?? '';
            $improvement = Improvement::query()->where('indicator_capture_id', $capture->id)->first();
            $this->improvementId = $improvement?->id;
            $this->improvementAnalysis = $improvement?->analysis ?? '';
            $this->improvementActionTaken = $improvement?->action_taken ?? '';
            $this->improvementActionDefined = $improvement?->action_defined ?? '';
            $this->improvementRequired = $improvement?->improvement_required ?? '';
        } else {
            $this->captureId = null;
            $this->improvementId = null;
            $this->form = $this->defaultForm();
            $this->analysisText = '';
            $this->improvementAnalysis = '';
            $this->improvementActionTaken = '';
            $this->improvementActionDefined = '';
            $this->improvementRequired = '';
        }

        $this->loadTrend();
        $this->computeCurrentMetrics();
        $this->buildSheetData();
    }

    protected function computeCurrentMetrics(): void
    {
        $calc = $this->calculateMetrics($this->form);

        $this->numerator = (float) ($calc['numerator'] ?? 0);
        $this->denominator = (float) ($calc['denominator'] ?? 0);
        $this->resultPercentage = (float) ($calc['result_percentage'] ?? 0);
        $this->complies = (bool) ($calc['complies'] ?? false);
        $this->metricErrors = $calc['errors'] ?? [];
        $this->semaforo = $this->complies ? 'VERDE' : 'ROJO';
    }

    protected function loadTrend(): void
    {
        $currentDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
        $months = collect([2, 1, 0])->map(function (int $offset) use ($currentDate): array {
            $d = $currentDate->copy()->subMonths($offset);
            return ['year' => (int) $d->year, 'month' => (int) $d->month];
        });

        $periods = Period::query()
            ->where(function ($query) use ($months): void {
                foreach ($months as $m) {
                    $query->orWhere(fn ($q) => $q->where('year', $m['year'])->where('month', $m['month']));
                }
            })
            ->get()
            ->keyBy(fn (Period $p) => $p->year.'-'.$p->month);

        $this->trendRows = $months->map(function (array $m) use ($periods): array {
            $period = $periods->get($m['year'].'-'.$m['month']);
            if (! $period || ! $this->selectedOperationsLeaderId) {
                return [
                    'year' => $m['year'],
                    'month' => $m['month'],
                    'result' => null,
                    'semaforo' => '-',
                ];
            }

            $capture = IndicatorCapture::query()->where([
                'indicator_id' => $this->indicator->id,
                'operations_leader_id' => $this->selectedOperationsLeaderId,
                'period_id' => $period->id,
            ])->first();

            return [
                'year' => $m['year'],
                'month' => $m['month'],
                'result' => $capture?->result_percentage,
                'semaforo' => $capture ? ($capture->complies ? 'VERDE' : 'ROJO') : '-',
            ];
        })->toArray();
    }

    protected function trendLabel(): string
    {
        $vals = collect($this->trendRows)
            ->pluck('result')
            ->filter(fn ($v) => $v !== null)
            ->values();

        if ($vals->count() < 2) {
            return 'sin datos suficientes';
        }

        return $vals->last() >= $vals->first() ? 'mejorando' : 'en descenso';
    }

    protected function historyLabel(): string
    {
        $vals = collect($this->trendRows)
            ->pluck('result')
            ->filter(fn ($v) => $v !== null);

        if ($vals->isEmpty()) {
            return 'sin datos';
        }

        return 'promedio '.round($vals->avg(), 2).' %';
    }

    protected function configureSheetLabels(): void
    {
        $fields = collect($this->indicator->required_fields ?? [])
            ->filter(fn ($field) => $field !== 'analisis_texto')
            ->values();

        $this->sheetDenominatorLabel = $this->humanizeFieldName((string) ($fields->get(0) ?? 'total_base'));
        $this->sheetNumeratorLabel = $this->humanizeFieldName((string) ($fields->get(1) ?? 'total_cumplido'));
    }

    protected function humanizeFieldName(string $field): string
    {
        return strtoupper(trim(str_replace('_', ' ', $field)));
    }

    protected function buildSheetData(): void
    {
        $monthNames = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
        ];

        if (! $this->selectedOperationsLeaderId) {
            $this->sheetRows = [];
            $this->chartPayload = [];
            return;
        }

        $periods = Period::query()
            ->where('year', $this->selectedYear)
            ->whereBetween('month', [1, 12])
            ->get(['id', 'month'])
            ->keyBy('month');

        $captures = IndicatorCapture::query()
            ->where('indicator_id', $this->indicator->id)
            ->where('operations_leader_id', $this->selectedOperationsLeaderId)
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

        $this->sheetRows = $rows;
        $this->chartPayload = [
            'months' => array_values($monthNames),
            'denominator' => $denominators,
            'numerator' => $numerators,
            'result_percentage' => $percentages,
            'meta' => array_fill(0, 12, (float) $this->indicator->target_value),
            'denominator_label' => $this->sheetDenominatorLabel,
            'numerator_label' => $this->sheetNumeratorLabel,
            'title' => 'Nivel de cumplimiento '.$this->indicator->name.' '.$this->selectedYear,
            'year' => $this->selectedYear,
        ];

        $this->dispatch('ft-op-01-chart-refresh', payload: $this->chartPayload);
    }

    protected function previousMonthResult(): ?float
    {
        if (count($this->trendRows) < 2) {
            return null;
        }

        $previous = $this->trendRows[count($this->trendRows) - 2]['result'] ?? null;
        return $previous !== null ? (float) $previous : null;
    }

    protected function buildImprovementBlock(): string
    {
        $block = "Analisis de resultados:\n".
            'Analisis: '.$this->improvementAnalysis."\n".
            'Accion tomada: '.$this->improvementActionTaken."\n".
            'Accion definida: '.$this->improvementActionDefined;

        if (! $this->complies && trim($this->improvementRequired) !== '') {
            $block .= "\n".'Debe agregar mejora: '.$this->improvementRequired;
        }

        return $block;
    }

    protected function validateImprovementFields(): void
    {
        $rules = [
            'improvementAnalysis' => ['required', 'string'],
            'improvementActionTaken' => ['required', 'string'],
            'improvementActionDefined' => ['required', 'string'],
        ];

        if (! $this->complies) {
            $rules['improvementRequired'] = ['required', 'string'];
        }

        $this->validate($rules);
    }

    protected function persistImprovement(IndicatorCapture $capture): void
    {
        $existing = Improvement::query()->where('indicator_capture_id', $capture->id)->first();
        $payload = [
            'indicator_capture_id' => $capture->id,
            'indicator_id' => $this->indicator->id,
            'operations_leader_id' => $this->selectedOperationsLeaderId,
            'period_id' => $this->periodId,
            'analysis' => $this->improvementAnalysis,
            'action_taken' => $this->improvementActionTaken,
            'action_defined' => $this->improvementActionDefined,
            'improvement_required' => $this->complies ? null : $this->improvementRequired,
            'integrated_analysis_block' => $this->analysisText,
            'created_by_user_id' => auth()->id(),
        ];

        if ($existing) {
            $beforeImprovement = $existing->toArray();
            $existing->update($payload);
            $this->auditLogService->logModelChange(
                eventType: 'improvement',
                action: 'update',
                model: $existing,
                before: $beforeImprovement,
                after: $existing->fresh()->toArray(),
                reason: 'Actualizacion analisis mensual'
            );
            $this->improvementId = $existing->id;
            return;
        }

        $improvement = Improvement::query()->create($payload);
        $this->auditLogService->logModelChange(
            eventType: 'improvement',
            action: 'create',
            model: $improvement,
            before: null,
            after: $improvement->toArray(),
            reason: 'Creacion analisis mensual'
        );
        $this->improvementId = $improvement->id;
    }

    protected function assertZoneAccess(): void
    {
        $allowedIds = array_map(static fn (array $leader): int => (int) $leader['id'], $this->operationsLeaders);

        if (! in_array((int) $this->selectedOperationsLeaderId, $allowedIds, true)) {
            abort(403);
        }
    }

    public function render()
    {
        $view = $this->indicator->code === 'FT-OP-03'
            ? 'livewire.indicadores.ft-op-03-form'
            : 'livewire.indicadores.ft-op-01-form';

        return view($view, [
            'fieldsView' => $this->fieldsView,
        ]);
    }
}

