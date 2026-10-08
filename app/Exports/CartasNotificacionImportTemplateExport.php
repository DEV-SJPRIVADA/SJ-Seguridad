<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plantilla Excel de carga a grilla (solo memoria; no persiste en BD).
 */
class CartasNotificacionImportTemplateExport
{
    public function download(string $fileName = 'plantilla_cartas_notificacion.xlsx'): StreamedResponse
    {
        // Sin título: fila 1 = encabezados (CEDULA…), listos para import-preview.
        return (new BaseExport(
            collect([]),
            [
                ['key' => 'cedula', 'label' => 'CEDULA'],
                ['key' => 'nombre_completo', 'label' => 'NOMBRE_COMPLETO'],
                ['key' => 'duracion_contrato', 'label' => 'DURACION_CONTRATO'],
                ['key' => 'fecha_terminacion', 'label' => 'FECHA_TERMINACION'],
                ['key' => 'firma', 'label' => 'FIRMA'],
            ],
            $fileName,
            ''
        ))->download();
    }
}
