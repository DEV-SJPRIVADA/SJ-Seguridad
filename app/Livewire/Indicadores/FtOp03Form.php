<?php

namespace App\Livewire\Indicadores;

use App\Models\IndicatorCapture;
use App\Models\Period;

class FtOp03Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-03';
    public array $financeRows = [];
    public array $incidentRows = [];
    public array $quarterlyTables = [];
    public array $financeChartPayload = [];
    public array $incidentChartPayload = [];
    public array $quarterChartPayload = [];
    public bool $showClassificationModal = false;
    public array $siniestroOptions = [
        'Hurto en apartamentos',
        'Hurto de accesorios de vehiculos',
        'Hurto de vehiculos / motos',
        'Hurto de elementos, dinero, bicicletas, electronicos, encomiendas fuera de los apartamentos',
        'Otros / afectaciones economicas',
    ];

    protected function defaultForm(): array
    {
        return [
            'total_servicios' => null,
            'total_siniestros' => null,
            'clasificacion_por_tipo' => [
                ['tipo' => '', 'cantidad' => null],
            ],
            'facturacion_mensual' => null,
            'valor_pagado_siniestros' => null,
        ];
    }

    protected function fieldRules(): array
    {
        $rules = [
            'form.total_servicios' => ['required', 'numeric', 'min:0.01'],
            'form.total_siniestros' => ['required', 'numeric', 'min:0'],
            'form.facturacion_mensual' => ['required', 'numeric', 'min:0.01'],
            'form.valor_pagado_siniestros' => ['required', 'numeric', 'min:0'],
        ];

        if ((float) ($this->form['total_siniestros'] ?? 0) >= 1) {
            $rules['form.clasificacion_por_tipo'] = ['required', 'array', 'min:1'];
            $rules['form.clasificacion_por_tipo.*.tipo'] = ['required', 'string'];
            $rules['form.clasificacion_por_tipo.*.cantidad'] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function updatedFormTotalSiniestros($value): void
    {
        if ((float) $value < 1) {
            $this->form['clasificacion_por_tipo'] = [
                ['tipo' => '', 'cantidad' => null],
            ];

            $this->resetValidation([
                'form.clasificacion_por_tipo',
                'form.clasificacion_por_tipo.*.tipo',
                'form.clasificacion_por_tipo.*.cantidad',
            ]);
        }

        $this->computeCurrentMetrics();
    }

    public function save(): void
    {
        if ((float) ($this->form['total_siniestros'] ?? 0) < 1) {
            $this->form['clasificacion_por_tipo'] = [
                ['tipo' => '', 'cantidad' => null],
            ];

            $this->resetValidation([
                'form.clasificacion_por_tipo',
                'form.clasificacion_por_tipo.*.tipo',
                'form.clasificacion_por_tipo.*.cantidad',
            ]);
        }

        parent::save();
    }

    public function addTypeRow(): void
    {
        $this->form['clasificacion_por_tipo'][] = ['tipo' => '', 'cantidad' => null];
    }

    public function removeTypeRow(int $index): void
    {
        unset($this->form['clasificacion_por_tipo'][$index]);
        $this->form['clasificacion_por_tipo'] = array_values($this->form['clasificacion_por_tipo']);
    }

    public function openClassificationModal(): void
    {
        if (! isset($this->form['clasificacion_por_tipo']) || ! is_array($this->form['clasificacion_por_tipo']) || count($this->form['clasificacion_por_tipo']) === 0) {
            $this->form['clasificacion_por_tipo'] = [['tipo' => '', 'cantidad' => null]];
        }
        $this->showClassificationModal = true;
    }

    public function closeClassificationModal(): void
    {
        $this->showClassificationModal = false;
    }

    public function saveClassification(): void
    {
        if ((float) ($this->form['total_siniestros'] ?? 0) < 1) {
            $this->form['clasificacion_por_tipo'] = [['tipo' => '', 'cantidad' => null]];
            $this->showClassificationModal = false;
            return;
        }

        $rows = collect($this->form['clasificacion_por_tipo'] ?? [])
            ->map(fn ($row) => [
                'tipo' => trim((string) ($row['tipo'] ?? '')),
                'cantidad' => $row['cantidad'],
            ])
            ->values();

        $this->validate([
            'form.clasificacion_por_tipo' => ['required', 'array', 'min:1'],
        ]);

        foreach ($rows as $index => $row) {
            if ($row['tipo'] !== '') {
                $this->validate([
                    "form.clasificacion_por_tipo.$index.cantidad" => ['required', 'numeric', 'min:0'],
                ]);
            }
        }

        $clean = $rows
            ->filter(fn ($row) => $row['tipo'] !== '')
            ->map(fn ($row) => ['tipo' => $row['tipo'], 'cantidad' => (float) $row['cantidad']])
            ->values()
            ->all();

        if (count($clean) === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.clasificacion_por_tipo' => 'Debes seleccionar al menos un tipo de siniestro.',
            ]);
        }

        $this->form['clasificacion_por_tipo'] = $clean;
        $this->showClassificationModal = false;
    }

    public function handleClassificationTypeChange(int $index): void
    {
        $rows = $this->form['clasificacion_por_tipo'] ?? [];
        if (! is_array($rows) || ! array_key_exists($index, $rows)) {
            return;
        }

        $currentType = trim((string) ($rows[$index]['tipo'] ?? ''));
        if ($currentType === '') {
            return;
        }

        $hasEmptyRow = collect($rows)->contains(function ($row): bool {
            return trim((string) ($row['tipo'] ?? '')) === '';
        });

        if (! $hasEmptyRow) {
            $this->form['clasificacion_por_tipo'][] = ['tipo' => '', 'cantidad' => null];
        }
    }

    protected function calculateMetrics(array $form): array
    {
        $totalServicios = (float) ($form['total_servicios'] ?? 0);
        $totalSiniestros = (float) ($form['total_siniestros'] ?? 0);
        $facturacion = (float) ($form['facturacion_mensual'] ?? 0);
        $valorPagado = (float) ($form['valor_pagado_siniestros'] ?? 0);
        $errors = [];

        if ($totalServicios <= 0) {
            $errors[] = 'total_servicios no puede ser 0.';
        }
        if ($facturacion <= 0) {
            $errors[] = 'facturacion_mensual no puede ser 0.';
        }

        $sumTipos = collect($form['clasificacion_por_tipo'] ?? [])->sum(fn ($row) => (float) ($row['cantidad'] ?? 0));
        if ($totalSiniestros >= 1 && round($sumTipos, 2) !== round($totalSiniestros, 2)) {
            $errors[] = 'La suma por tipo debe ser igual a total_siniestros.';
        }

        $freq = $totalServicios > 0 ? round(($totalSiniestros / $totalServicios) * 100, 2) : 0;
        $impacto = $facturacion > 0 ? round(($valorPagado / $facturacion) * 100, 2) : 0;
        $cumpleA = $totalServicios > 0 && $freq <= 3;
        $cumpleB = $facturacion > 0 && $impacto <= 1;
        $complies = $cumpleA && $cumpleB;

        return [
            'numerator' => $totalSiniestros + $valorPagado,
            'denominator' => $totalServicios + $facturacion,
            'result_percentage' => $freq,
            'complies' => $complies,
            'errors' => $errors,
        ];
    }

    protected function loadContext(): void
    {
        parent::loadContext();
        $this->buildFtOp03Data();
    }

    private function buildFtOp03Data(): void
    {
        $monthNames = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
        ];

        if (! $this->selectedOperationsLeaderId) {
            $this->financeRows = [];
            $this->incidentRows = [];
            $this->quarterlyTables = [];
            $this->financeChartPayload = [];
            $this->incidentChartPayload = [];
            $this->quarterChartPayload = [];
            $this->dispatch(
                'ft-op-03-charts-refresh',
                finance: $this->financeChartPayload,
                clients: $this->incidentChartPayload,
                quarters: $this->quarterChartPayload
            );
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

        $defaultTypes = [
            'Hurto en apartamentos',
            'Hurto de accesorios de vehiculos',
            'Hurto de vehiculos / motos',
            'Hurto de elementos, dinero, bicicletas, electronicos, encomiendas fuera de los apartamentos',
            'Otros / afectaciones economicas',
        ];

        $allTypes = collect($defaultTypes);
        foreach ($quarterTypeTotals as $types) {
            foreach (array_keys($types) as $type) {
                if (! $allTypes->contains($type)) {
                    $allTypes->push($type);
                }
            }
        }

        $typeList = $allTypes->take(5)->values()->all();
        $this->quarterlyTables = [];
        $this->quarterChartPayload = [];

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

            $this->quarterlyTables[$q] = [
                'rows' => $rows,
                'total_qty' => $sum,
            ];
            $this->quarterChartPayload[$q] = [
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

        $this->financeRows = [
            'facturacion' => $facturacion,
            'pagado' => $valorPagado,
            'cumplimiento' => $pctCumplimiento,
            'totals' => ['facturacion' => $sumFact, 'pagado' => $sumPago, 'cumplimiento' => $sumPct],
        ];

        $this->incidentRows = [
            'clientes' => $clientes,
            'siniestros' => $siniestros,
            'porcentaje' => $pctSiniestros,
            'totals' => ['clientes' => $sumCli, 'siniestros' => $sumSin, 'porcentaje' => $sumSinPct],
        ];

        $this->financeChartPayload = [
            'months' => array_values($monthNames),
            'facturacion' => array_values($facturacion),
            'pagado' => array_values($valorPagado),
            'cumplimiento' => array_values($pctCumplimiento),
        ];

        $this->incidentChartPayload = [
            'months' => array_values($monthNames),
            'clientes' => array_values($clientes),
            'siniestros' => array_values($siniestros),
            'porcentaje' => array_values($pctSiniestros),
        ];

        $this->dispatch(
            'ft-op-03-charts-refresh',
            finance: $this->financeChartPayload,
            clients: $this->incidentChartPayload,
            quarters: $this->quarterChartPayload
        );
    }
}
