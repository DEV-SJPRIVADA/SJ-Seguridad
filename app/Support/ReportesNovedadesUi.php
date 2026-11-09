<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

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

    /**
     * Celda HTML de cédula: en rojo cuando la cédula se repite en el listado filtrado.
     */
    public static function cedulaCell(mixed $value, bool $isDuplicate): string
    {
        $text = trim((string) $value);

        if ($isDuplicate && $text !== '') {
            return '<span class="rn-cedula-duplicada" title="Cédula duplicada en este listado">'.e($text).'</span>';
        }

        return e((string) $value);
    }

    /**
     * Cédulas que aparecen más de una vez en la consulta indicada.
     *
     * @param  Builder<*>  $query
     * @return array<string, true>
     */
    public static function duplicateDocumentNumbers(Builder $query): array
    {
        $values = (clone $query)
            ->reorder()
            ->select('document_number')
            ->whereNotNull('document_number')
            ->where('document_number', '!=', '')
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('document_number');

        $duplicates = [];
        foreach ($values as $value) {
            $duplicates[trim((string) $value)] = true;
        }

        return $duplicates;
    }
}
