<?php

namespace App\Exports;

use App\Models\AcreditacionExportApoSetting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export SuperVigilancia APO en formato .xls (1 hoja, headers A–X).
 * No usa BaseExport (emite .xlsx).
 */
class AcreditacionExportApoXlsExport
{
    /**
     * @param  list<array<string, mixed>>  $rows  Filas ya filtradas (sin Valida/motivo)
     */
    public function download(array $rows, AcreditacionExportApoSetting $settings, string $fileName): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($rows, $settings);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xls($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function buildSpreadsheet(array $rows, AcreditacionExportApoSetting $settings): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ApoDatos');

        /** @var list<string> $headers */
        $headers = array_values(config('acreditaciones.export_apo.headers', []));

        foreach ($headers as $index => $header) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $header);
        }

        $excelRow = 2;
        foreach ($rows as $row) {
            $values = $this->mapRowValues($row, $settings);
            foreach ($values as $index => $value) {
                $col = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValueExplicit(
                    $col.$excelRow,
                    (string) $value,
                    DataType::TYPE_STRING,
                );
            }
            $excelRow++;
        }

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function mapRowValues(array $row, AcreditacionExportApoSetting $settings): array
    {
        return [
            (string) $settings->nit,
            (string) $settings->razon_social,
            (string) $settings->tipo_documento,
            (string) ($row['document_number'] ?? ''),
            (string) ($row['nombre1'] ?? ''),
            (string) ($row['nombre2'] ?? ''),
            (string) ($row['apellido1'] ?? ''),
            (string) ($row['apellido2'] ?? ''),
            (string) ($row['fecha_nacimiento'] ?? ''),
            (string) ($row['genero'] ?? ''),
            (string) ($row['cargo'] ?? ''),
            (string) ($row['fecha_vinculacion'] ?? ''),
            (string) ($row['codigo_curso'] ?? ''),
            (string) ($row['nit_escuela'] ?? ''),
            (string) ($row['nro'] ?? ''),
            (string) $settings->tipo_establecimiento,
            (string) $settings->telefono_r,
            (string) $settings->direccion_r,
            (string) $settings->direccion_p,
            (string) $settings->departamento,
            (string) $settings->ciudad,
            (string) $settings->educacion_bm,
            (string) $settings->educacion_s,
            (string) $settings->discapacidad,
        ];
    }
}
