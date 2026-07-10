<?php

namespace App\Livewire\Indicadores;

class FtOp05Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-05';

    protected function defaultForm(): array
    {
        return ['visitas_programadas' => null, 'visitas_realizadas' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.visitas_programadas' => ['required', 'numeric', 'min:0.01'],
            'form.visitas_realizadas' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['visitas_programadas'] ?? 0);
        $num = (float) ($form['visitas_realizadas'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'visitas_programadas no puede ser 0.';
        }
        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $den > 0 && $result >= 100,
            'errors' => $errors,
        ];
    }
}
