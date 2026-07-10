<?php

namespace App\Livewire\Indicadores;

class FtOp02Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-02';

    protected function defaultForm(): array
    {
        return ['total_servicios' => null, 'no_conformes' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.total_servicios' => ['required', 'numeric', 'min:0.01'],
            'form.no_conformes' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['total_servicios'] ?? 0);
        $num = (float) ($form['no_conformes'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'total_servicios no puede ser 0.';
        }

        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0;
        $complies = $den > 0 && $result <= (float) $this->indicator->target_value;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $complies,
            'errors' => $errors,
        ];
    }
}
