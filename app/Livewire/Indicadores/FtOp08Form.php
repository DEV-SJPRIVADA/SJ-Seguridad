<?php

namespace App\Livewire\Indicadores;

class FtOp08Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-08';

    protected function defaultForm(): array
    {
        return ['inventarios_programados' => null, 'inventarios_realizados' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.inventarios_programados' => ['required', 'numeric', 'min:0.01'],
            'form.inventarios_realizados' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['inventarios_programados'] ?? 0);
        $num = (float) ($form['inventarios_realizados'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'inventarios_programados no puede ser 0.';
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
