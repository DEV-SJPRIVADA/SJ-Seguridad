<?php

namespace App\Livewire\Indicadores;

class FtOp01Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-01';

    protected function defaultForm(): array
    {
        return ['total_personal' => null, 'personal_capacitado' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.total_personal' => ['required', 'numeric', 'min:0.01'],
            'form.personal_capacitado' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['total_personal'] ?? 0);
        $num = (float) ($form['personal_capacitado'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'total_personal no puede ser 0.';
        }

        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0;
        $complies = $den > 0 && $result >= (float) $this->indicator->target_value;

        return compact('num', 'den', 'result', 'complies', 'errors') + [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
        ];
    }

    protected function configureSheetLabels(): void
    {
        $this->sheetDenominatorLabel = 'TOTAL PERSONAL OPERATIVO';
        $this->sheetNumeratorLabel = 'PERSONAL OPERATIVO CAPACITADO POR ZONA';
    }
}
