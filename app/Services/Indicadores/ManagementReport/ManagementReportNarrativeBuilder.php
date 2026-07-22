<?php

namespace App\Services\Indicadores\ManagementReport;

use App\Models\Indicator;

class ManagementReportNarrativeBuilder
{
    public function build(
        Indicator $indicator,
        string $monthName,
        ?float $result,
        ?float $previousResult,
        string $metaLabel,
    ): string {
        if ($result === null) {
            return sprintf(
                'En el mes de %s no se registro captura consolidada para el indicador %s (%s).',
                $monthName,
                $indicator->code,
                $indicator->name
            );
        }

        $resultText = number_format($result, 2).'%';
        $comparison = $this->comparisonPhrase($result, $previousResult);
        $reading = $this->readingPhrase($indicator, $result);

        return sprintf(
            'En el mes de %s el indicador %s alcanzo un resultado del %s%s Meta: %s. %s',
            $monthName,
            $indicator->name,
            $resultText,
            $comparison,
            $metaLabel,
            $reading
        );
    }

    private function comparisonPhrase(float $result, ?float $previousResult): string
    {
        if ($previousResult === null) {
            return '.';
        }

        $delta = round($result - $previousResult, 2);

        if ($delta > 0) {
            return ', con un incremento de '.number_format(abs($delta), 2).' puntos porcentuales respecto al mes anterior.';
        }

        if ($delta < 0) {
            return ', con una disminucion de '.number_format(abs($delta), 2).' puntos porcentuales respecto al mes anterior.';
        }

        return ', manteniendo el mismo resultado del mes anterior.';
    }

    private function readingPhrase(Indicator $indicator, float $result): string
    {
        $complies = match ($indicator->target_operator) {
            '>=' => $result >= (float) $indicator->target_value,
            '<=' => $result <= (float) $indicator->target_value,
            '==' => round($result, 2) === round((float) $indicator->target_value, 2),
            default => false,
        };

        if ($indicator->code === 'FT-OP-03') {
            return $complies
                ? 'La operacion se mantiene dentro de los limites de siniestralidad definidos.'
                : 'Se requiere seguimiento a la siniestralidad operativa y su impacto economico.';
        }

        return $complies
            ? 'El indicador cumple la meta del periodo.'
            : 'El indicador requiere acciones de mejora para alcanzar la meta del periodo.';
    }
}
