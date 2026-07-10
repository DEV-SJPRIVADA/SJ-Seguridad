<?php

namespace App\Livewire\Indicadores;

class FtOp09Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-09';

    protected function defaultForm(): array
    {
        return ['armas_programadas' => null, 'armas_inspeccionadas' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.armas_programadas' => ['required', 'numeric', 'min:0.01'],
            'form.armas_inspeccionadas' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['armas_programadas'] ?? 0);
        $num = (float) ($form['armas_inspeccionadas'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'armas_programadas no puede ser 0.';
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
