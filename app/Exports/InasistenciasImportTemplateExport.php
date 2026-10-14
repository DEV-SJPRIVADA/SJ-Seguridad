<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plantilla Excel de carga a grilla Inasistencias (solo memoria; no persiste en BD).
 */
class InasistenciasImportTemplateExport
{
    public function download(string $fileName = 'plantilla_inasistencias.xlsx'): StreamedResponse
    {
        return (new BaseExport(
            collect([]),
            [
                ['key' => 'cedula', 'label' => 'CEDULA'],
                ['key' => 'nombre_completo', 'label' => 'NOMBRE'],
                ['key' => 'fecha_ini_aus', 'label' => 'FECHA_INI_AUS'],
                ['key' => 'firma', 'label' => 'FIRMA'],
            ],
            $fileName,
            ''
        ))->download();
    }
}
