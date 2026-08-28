<?php

namespace App\Exports;

use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\PlantillaMasivosMapper;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantillaMasivosExport
{
    public function __construct(
        private readonly PlantillaMasivosMapper $mapper,
    ) {}

    /**
     * @param  Collection<int, PersonalRequisitionFichaEntry>  $entries
     */
    public function download(Collection $entries, string $fileName): StreamedResponse
    {
        $templatePath = config('employee_ficha.plantilla_masivos_template');

        if (! is_readable($templatePath)) {
            throw new \RuntimeException('Plantilla masivos no encontrada en: '.$templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $startRow = 3;
        $row = $startRow;

        foreach ($entries as $entry) {
            $values = $this->mapper->mapRow($entry);
            foreach ($values as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($column.$row, $value ?? null);
            }
            $row++;
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
