<?php

namespace App\Services\Supplies;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SupplyPurchaseReportExporter
{
    /**
     * @return Collection<int, array{quantity: int, description: string, reference: string, utilization: string, location: string, foto_path: ?string}>
     */
    public function buildMergedRowsForRequest(SupplyRequest $request): Collection
    {
        $items = $request->items()
            ->with('product')
            ->where('approved_quantity', '>', 0)
            ->get();

        return $this->mergeItems($items, $request);
    }

    /**
     * @param  Collection<int, array{quantity: int, description: string, reference: string, utilization: string, location: string, foto_path: ?string}>  $rows
     */
    public function toDownloadResponseForRequest(SupplyRequest $request, Collection $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Solicitudes');

        $this->buildHeaderForRequest($sheet, $request);

        $headerRow = 6;
        // Columnas oficiales FO-AD-44 Marzo 2026 Versión 01
        $headers = [
            'Cantidad',
            'Foto',
            'Descripción',
            'Referencia',
            'Utilización',
            'Ubicación',
        ];

        foreach ($headers as $index => $label) {
            $column = chr(ord('A') + $index);
            $cell = $column.$headerRow;
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF003366');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $photoPlaceholder = (string) config('supplies.report.photo_placeholder', 'S/A');
        $dataRow = $headerRow + 1;

        foreach ($rows as $row) {
            $sheet->setCellValue('A'.$dataRow, $row['quantity']);
            $embedded = $this->embedItemPhoto($sheet, 'B'.$dataRow, $row['foto_path'] ?? null);
            if (! $embedded) {
                $sheet->setCellValue('B'.$dataRow, $photoPlaceholder);
            }
            $sheet->setCellValue('C'.$dataRow, $row['description']);
            $sheet->setCellValue('D'.$dataRow, $row['reference']);
            $sheet->setCellValue('E'.$dataRow, $row['utilization']);
            $sheet->setCellValue('F'.$dataRow, $row['location']);
            $sheet->getStyle('A'.$dataRow.':F'.$dataRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $dataRow++;
        }

        $lastRow = max($dataRow - 1, $headerRow);
        $sheet->setAutoFilter('A'.$headerRow.':F'.$lastRow);
        $sheet->getStyle('A'.$headerRow.':F'.$lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(14);
        foreach (range('C', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            '%s_solicitud-%d_%s.xlsx',
            config('supplies.report.form_code', 'FO-AD-44'),
            $request->id,
            now()->format('Y-m-d')
        );

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, SupplyRequestItem>  $items
     * @return Collection<int, array{quantity: int, description: string, reference: string, utilization: string, location: string, foto_path: ?string}>
     */
    private function mergeItems(Collection $items, SupplyRequest $request): Collection
    {
        return $items->groupBy(function (SupplyRequestItem $item): string {
            $description = mb_strtolower(trim($item->displayName()));
            $reference = mb_strtolower(trim($item->referenceLabel()));

            return $description.'|'.$reference;
        })->map(function (Collection $group) use ($request): array {
            /** @var SupplyRequestItem $first */
            $first = $group->first();

            return [
                'quantity' => (int) $group->sum('approved_quantity'),
                'description' => $first->displayName(),
                'reference' => $first->referenceLabel(),
                'utilization' => (string) $request->site_utilization,
                'location' => (string) $request->site_city,
                // Catálogo de insumos aún no tiene foto por producto; se intenta embeber si llega path.
                'foto_path' => null,
            ];
        })->values();
    }

    private function buildHeaderForRequest(Worksheet $sheet, SupplyRequest $request): void
    {
        $logoPath = public_path('images/logoSj.png');

        if (is_file($logoPath)) {
            try {
                $drawing = new Drawing;
                $drawing->setName('Logo SJ');
                $drawing->setPath($logoPath);
                $drawing->setHeight(48);
                $drawing->setCoordinates('A1');
                $drawing->setWorksheet($sheet);
            } catch (Throwable) {
                // Sin logo si el archivo no es legible.
            }
        }

        $sheet->mergeCells('B1:E1');
        $sheet->setCellValue('B1', config('supplies.report.title', 'SOLICITUDES DE COMPRAS'));
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $period = (string) config('supplies.report.period', 'Marzo 2026');
        $version = (string) config('supplies.report.version', '01');

        $meta = [
            'F1' => config('supplies.report.form_code', 'FO-AD-44'),
            'F2' => $period,
            'F3' => 'Versión '.$version,
            'F4' => 'Página 1 de 1',
        ];

        foreach ($meta as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $utilization = $request->site_utilization ?: 'Sin sede';
        $city = $request->site_city ?: '—';

        $sheet->setCellValue('A5', 'Solicitud #'.$request->id.' | Sede: '.$utilization.' ('.$city.')');
        $sheet->getStyle('A5')->getFont()->setItalic(true);
    }

    private function embedItemPhoto(Worksheet $sheet, string $coordinate, ?string $fotoPath): bool
    {
        if ($fotoPath === null || $fotoPath === '') {
            return false;
        }

        try {
            if (! Storage::disk('public')->exists($fotoPath)) {
                return false;
            }

            $absolute = Storage::disk('public')->path($fotoPath);
            if (! is_file($absolute)) {
                return false;
            }

            $drawing = new Drawing;
            $drawing->setName('Foto ítem');
            $drawing->setPath($absolute);
            $drawing->setHeight(48);
            $drawing->setCoordinates($coordinate);
            $drawing->setWorksheet($sheet);

            $row = (int) preg_replace('/\D+/', '', $coordinate);
            if ($row > 0) {
                $sheet->getRowDimension($row)->setRowHeight(52);
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
