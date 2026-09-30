<?php

namespace App\Support;

final class ReportesNovedadesUi
{
    /**
     * Celda HTML de observación Nómina: verde cuando ya hay valor.
     */
    public static function observacionNominaCell(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return e('—');
        }

        return '<span class="status-pill status-pill--success rn-obs-nomina-cell" title="'.e($text).'">'.e($text).'</span>';
    }
}
