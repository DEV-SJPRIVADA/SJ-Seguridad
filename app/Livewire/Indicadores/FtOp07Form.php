<?php

namespace App\Livewire\Indicadores;

class FtOp07Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-07';

    protected function defaultForm(): array
    {
        return ['analisis_programados' => null, 'analisis_realizados' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.analisis_programados' => ['required', 'numeric', 'min:0.01'],
            'form.analisis_realizados' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['analisis_programados'] ?? 0);
        $num = (float) ($form['analisis_realizados'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'analisis_programados no puede ser 0.';
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
