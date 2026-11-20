<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plantilla de importación MT-GI-11.
 *
 * Fila 1 = claves internas (las lee el importador), fila 2 = etiquetas visibles.
 * Los catálogos se capturan por nombre y el importador los resuelve; si un
 * valor no existe, la fila se reporta como error sin guardar.
 */
class MtGi11ImportTemplateExport
{
    /**
     * Claves internas en el mismo orden que `MtGi11ImportService::$columns`.
     *
     * @return array<string, string>
     */
    public static function columns(): array
    {
        return [
            'fecha_solicitud' => 'Fecha de solicitud (AAAA-MM-DD, opcional: vacío = hoy)',
            'proceso' => 'Proceso',
            'consecutivo' => 'Consecutivo de solicitud',
            'tipo_documento' => 'Tipo de documento',
            'nombre_documento' => 'Nombre del documento',
            'tipo_solicitud' => 'Tipo de solicitud',
            'codigo_actual' => 'Código actual',
            'version_actual' => 'Versión actual',
            'justificacion' => 'Justificación de la solicitud',
            'responsable_proceso' => 'Nombre del responsable del proceso',
            'cargo_responsable' => 'Cargo del responsable del proceso',
            'codigo_estandarizacion' => 'Código de estandarización',
            'fecha_estandarizacion' => 'Fecha de estandarización (AAAA-MM-DD)',
            'version_actualizada' => 'Versión actualizada (número)',
            'estado' => 'Estado',
        ];
    }

    public function download(string $fileName = 'plantilla_mt_gi_11.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $colIndex = 1;

        foreach (self::columns() as $key => $label) {
            $col = Coordinate::stringFromColumnIndex($colIndex);

            $sheet->setCellValue($col.'1', $key);
            $sheet->getStyle($col.'1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');

            $sheet->setCellValue($col.'2', $label);
            $sheet->getStyle($col.'2')->getFont()->setItalic(true);

            $colIndex++;
        }

        // Fila 3 deja una fila guía para que el usuario vea el formato esperado.
        $sheet->freezePane('A4');
        $sheet->getRowDimension(1)->setVisible(false);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
