<?php

namespace App\Livewire\Indicadores;

class FtOp04Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-04';

    protected function defaultForm(): array
    {
        return ['supervisiones_programadas' => null, 'supervisiones_realizadas' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.supervisiones_programadas' => ['required', 'numeric', 'min:0.01'],
            'form.supervisiones_realizadas' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['supervisiones_programadas'] ?? 0);
        $num = (float) ($form['supervisiones_realizadas'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'supervisiones_programadas no puede ser 0.';
        }
        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $den > 0 && $result >= 90,
            'errors' => $errors,
        ];
    }
}
