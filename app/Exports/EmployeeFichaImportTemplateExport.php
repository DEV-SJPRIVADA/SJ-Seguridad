<?php

namespace App\Exports;

use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\EmployeeFichaImportRowMapper;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeFichaImportTemplateExport
{
    public function __construct(
        private readonly EmployeeFichaImportRowMapper $rowMapper,
    ) {}

    public function download(string $fileName = 'plantilla_importacion_ficha_empleados.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $this->writeHeaders($spreadsheet->getActiveSheet());

        return $this->streamDownload($spreadsheet, $fileName);
    }

    /**
     * @param  Collection<int, PersonalRequisitionFichaEntry>  $entries
     */
    public function downloadWithData(Collection $entries, string $fileName): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $columns = $this->writeHeaders($sheet);
        $row = 3;

        foreach ($entries as $entry) {
            $values = $this->rowMapper->mapRow($entry);
            $colIndex = 1;

            foreach (array_keys($columns) as $key) {
                $value = $values[$key] ?? null;

                if ($value !== null && $value !== '') {
                    $col = Coordinate::stringFromColumnIndex($colIndex);

                    if ($key === 'cedula') {
                        $sheet->setCellValueExplicit($col.$row, (string) $value, DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValue($col.$row, $value);
                    }
                }

                $colIndex++;
            }

            $row++;
        }

        return $this->streamDownload($spreadsheet, $fileName);
    }

    /**
     * @return array<string, string>
     */
    private function writeHeaders(Worksheet $sheet): array
    {
        /** @var array<string, string> $columns */
        $columns = config('employee_ficha.import_columns', []);
        $colIndex = 1;

        foreach ($columns as $key => $label) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($col.'1', $key);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $sheet->setCellValue($col.'2', $label);
            $colIndex++;
        }

        $sheet->freezePane('A3');

        return $columns;
    }

    private function streamDownload(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
