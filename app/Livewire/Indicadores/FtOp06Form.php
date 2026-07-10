<?php

namespace App\Livewire\Indicadores;

class FtOp06Form extends BaseIndicatorForm
{
    protected string $fieldsView = 'livewire.indicadores.partials.ft-op-06';

    protected function defaultForm(): array
    {
        return ['total_clientes_cadena' => null, 'eventos_indeseables' => null];
    }

    protected function fieldRules(): array
    {
        return [
            'form.total_clientes_cadena' => ['required', 'numeric', 'min:0.01'],
            'form.eventos_indeseables' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function calculateMetrics(array $form): array
    {
        $den = (float) ($form['total_clientes_cadena'] ?? 0);
        $num = (float) ($form['eventos_indeseables'] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = 'total_clientes_cadena no puede ser 0.';
        }
        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $den > 0 && $result == 0.0,
            'errors' => $errors,
        ];
    }
}
