<?php

namespace App\Exports;

use App\Models\CommercialService;
use App\Services\Comercial\CommercialMatrixImportRowMapper;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialMatrixImportTemplateExport
{
    public function __construct(
        private readonly CommercialMatrixImportRowMapper $rowMapper,
    ) {}

    public function download(string $fileName = 'plantilla_importacion_matriz_comercial.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(config('commercial_matrix.sheet_name', 'Matriz comercial'));
        $this->writeHeaders($sheet);

        return $this->streamDownload($spreadsheet, $fileName);
    }

    /**
     * @param  Collection<int, CommercialService>  $services
     */
    public function downloadWithData(Collection $services, string $fileName): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(config('commercial_matrix.sheet_name', 'Matriz comercial'));
        $columns = $this->writeHeaders($sheet);
        $row = (int) config('commercial_matrix.data_start_row', 3);

        foreach ($services as $service) {
            $values = $this->rowMapper->mapRow($service);
            $colIndex = 1;

            foreach (array_keys($columns) as $key) {
                $value = $values[$key] ?? null;

                if ($value !== null && $value !== '') {
                    $col = Coordinate::stringFromColumnIndex($colIndex);

                    if (in_array($key, ['nit', 'contract_number', 'legal_rep_doc', 'contact_phone'], true)) {
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
        $columns = config('commercial_matrix.import_columns', []);
        $keyRow = (int) config('commercial_matrix.header_key_row', 1);
        $labelRow = (int) config('commercial_matrix.header_label_row', 2);
        $colIndex = 1;

        foreach ($columns as $key => $label) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($col.$keyRow, $key);
            $sheet->getStyle($col.$keyRow)->getFont()->setBold(true);
            $sheet->setCellValue($col.$labelRow, $label);
            $colIndex++;
        }

        $sheet->freezePane('A'.config('commercial_matrix.data_start_row', 3));

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
