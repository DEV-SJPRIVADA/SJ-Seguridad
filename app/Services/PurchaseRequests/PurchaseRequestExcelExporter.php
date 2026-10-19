<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
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

class PurchaseRequestExcelExporter
{
    /**
     * @return Collection<int, array{quantity: int, description: string, reference: string, utilization: string, location: string, foto_path: ?string}>
     */
    public function buildRows(PurchaseRequest $purchaseRequest): Collection
    {
        return $purchaseRequest->items->map(fn (PurchaseRequestItem $item): array => [
            'quantity' => (int) $item->cantidad,
            'description' => $item->descripcion,
            'reference' => $item->referencia,
            'utilization' => $item->utilizacion,
            'location' => $item->ubicacion,
            'foto_path' => $item->foto_path,
        ]);
    }

    public function toDownloadResponse(PurchaseRequest $purchaseRequest): StreamedResponse
    {
        $rows = $this->buildRows($purchaseRequest);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Solicitud');

        $this->buildOfficialHeader($sheet, $purchaseRequest);

        // Columnas oficiales FO-AD-44 Marzo 2026 Versión 01
        $headers = ['Cantidad', 'Foto', 'Descripción', 'Referencia', 'Utilización', 'Ubicación'];
        $headerRow = 6;
        foreach ($headers as $index => $label) {
            $cell = chr(65 + $index).$headerRow;
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $photoPlaceholder = (string) config('purchase-requests.photo_placeholder', 'S/A');
        $row = $headerRow + 1;

        foreach ($rows as $dataRow) {
            $sheet->setCellValue('A'.$row, $dataRow['quantity']);
            $embedded = $this->embedItemPhoto($sheet, 'B'.$row, $dataRow['foto_path'] ?? null);
            if (! $embedded) {
                $sheet->setCellValue('B'.$row, $photoPlaceholder);
            }
            $sheet->setCellValue('C'.$row, $dataRow['description']);
            $sheet->setCellValue('D'.$row, $dataRow['reference']);
            $sheet->setCellValue('E'.$row, $dataRow['utilization']);
            $sheet->setCellValue('F'.$row, $dataRow['location']);
            $sheet->getStyle('A'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B'.$row.':F'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row++;
        }

        $lastRow = max($row - 1, $headerRow);
        $sheet->getStyle('A'.$headerRow.':F'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(14);
        foreach (range('C', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'FO-AD-44-Solicitud-'.$purchaseRequest->folio().'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildOfficialHeader(Worksheet $sheet, PurchaseRequest $purchaseRequest): void
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
                // Sin logo si el archivo no es legible para PhpSpreadsheet.
            }
        }

        $sheet->mergeCells('B1:E1');
        $sheet->setCellValue('B1', config('purchase-requests.report_title', 'SOLICITUDES DE COMPRAS'));
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $period = (string) config('purchase-requests.form_period', 'Marzo 2026');
        $version = (string) config('purchase-requests.form_version', '01');

        $meta = [
            'F1' => (string) config('purchase-requests.form_code', 'FO-AD-44'),
            'F2' => $period,
            'F3' => 'Versión '.$version,
            'F4' => 'Página 1 de 1',
        ];

        foreach ($meta as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->setCellValue('A5', 'Solicitud N.º: '.$purchaseRequest->folio());
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
