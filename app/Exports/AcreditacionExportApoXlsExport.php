<?php

namespace App\Exports;

use App\Models\AcreditacionExportApoSetting;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export SuperVigilancia APO en formato .xls (hojas ApoDatos + Retiros).
 * No usa BaseExport (emite .xlsx).
 */
class AcreditacionExportApoXlsExport
{
    public const MODE_ACREDITAR = 'acreditar';

    public const MODE_RETIRAR = 'retirar';

    /**
     * @param  list<array<string, mixed>>  $rows  Filas ya filtradas (sin Valida/motivo)
     */
    public function download(
        array $rows,
        AcreditacionExportApoSetting $settings,
        string $fileName,
        string $exportMode = self::MODE_ACREDITAR,
    ): StreamedResponse {
        $spreadsheet = $this->buildSpreadsheet($rows, $settings, $exportMode);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xls($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function buildSpreadsheet(
        array $rows,
        AcreditacionExportApoSetting $settings,
        string $exportMode = self::MODE_ACREDITAR,
    ): Spreadsheet {
        $mode = $this->normalizeMode($exportMode);
        $spreadsheet = new Spreadsheet;

        $apoSheet = $spreadsheet->getActiveSheet();
        $apoSheet->setTitle('ApoDatos');
        $this->writeHeaderRow($apoSheet, array_values(config('acreditaciones.export_apo.headers', [])));

        if ($mode === self::MODE_ACREDITAR) {
            $this->writeApoDatosRows($apoSheet, $rows, $settings);
        }

        $retirosSheet = $spreadsheet->createSheet();
        $retirosSheet->setTitle('Retiros');
        $this->writeHeaderRow($retirosSheet, array_values(config('acreditaciones.export_apo.retiros_headers', [])));

        if ($mode === self::MODE_RETIRAR) {
            $this->writeRetirosRows($retirosSheet, $rows, $settings);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function normalizeMode(string $mode): string
    {
        $mode = trim($mode);

        return $mode === self::MODE_RETIRAR ? self::MODE_RETIRAR : self::MODE_ACREDITAR;
    }

    /**
     * @param  list<string>  $headers
     */
    private function writeHeaderRow(Worksheet $sheet, array $headers): void
    {
        foreach ($headers as $index => $header) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $header);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeApoDatosRows(Worksheet $sheet, array $rows, AcreditacionExportApoSetting $settings): void
    {
        $excelRow = 2;
        foreach ($rows as $row) {
            $values = $this->mapApoDatosValues($row, $settings);
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
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeRetirosRows(Worksheet $sheet, array $rows, AcreditacionExportApoSetting $settings): void
    {
        // SuperVigilancia: FechaRetiro = día anterior (Bogotá), no la fecha de generación.
        $fechaRetiro = Carbon::now('America/Bogota')->subDay()->format('d/m/Y');
        $excelRow = 2;

        foreach ($rows as $row) {
            $values = [
                (string) $settings->nit,
                (string) $settings->razon_social,
                (string) $settings->tipo_documento,
                (string) ($row['document_number'] ?? ''),
                $fechaRetiro,
            ];

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
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function mapApoDatosValues(array $row, AcreditacionExportApoSetting $settings): array
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
