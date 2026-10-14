<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Excel del histórico de cartas de inasistencia (solo registro, sin archivo Word).
 */
class InasistenciasHistoricoExport
{
    /**
     * @param  Collection<int, object|array<string, mixed>>  $rows
     */
    public function download(Collection $rows, string $fileName = 'inasistencias_historico.xlsx'): StreamedResponse
    {
        return (new BaseExport(
            $rows,
            [
                ['key' => 'document_number', 'label' => 'CÉDULA'],
                ['key' => 'full_name', 'label' => 'NOMBRE'],
                ['key' => 'cargo', 'label' => 'CARGO'],
                ['key' => 'ciudad', 'label' => 'CIUDAD'],
                ['key' => 'fecha_ini_aus', 'label' => 'FECHA INI AUS'],
                ['key' => 'dias', 'label' => 'DÍAS'],
                ['key' => 'firma_name', 'label' => 'FIRMA'],
                ['key' => 'cargo_firma', 'label' => 'CARGO FIRMA'],
                ['key' => 'generated_at', 'label' => 'GENERADO'],
                ['key' => 'generated_by_name', 'label' => 'GENERADO POR'],
            ],
            $fileName,
            'Histórico Inasistencias'
        ))->download();
    }
}
