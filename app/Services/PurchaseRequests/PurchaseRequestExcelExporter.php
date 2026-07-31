<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseRequestExcelExporter
{
    /**
     * @return Collection<int, array{quantity: int, description: string, reference: string, utilization: string, location: string}>
     */
    public function buildRows(PurchaseRequest $purchaseRequest): Collection
    {
        return $purchaseRequest->items->map(fn (PurchaseRequestItem $item): array => [
            'quantity' => (int) $item->cantidad,
            'description' => $item->descripcion,
            'reference' => $item->referencia,
            'utilization' => $item->utilizacion,
            'location' => $item->ubicacion,
        ]);
    }

    public function toDownloadResponse(PurchaseRequest $purchaseRequest): StreamedResponse
    {
        $rows = $this->buildRows($purchaseRequest);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Solicitud');

        $sheet->setCellValue('A1', config('purchase-requests.report_title', 'SOLICITUDES DE COMPRAS'));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Formulario: '.config('purchase-requests.form_code'));
        $sheet->setCellValue('A3', 'Solicitud N.º: '.$purchaseRequest->folio());

        $headers = ['Cantidad', 'Foto', 'Descripcion', 'Referencia', 'Utilizacion', 'Ubicacion'];
        $headerRow = 5;
        foreach ($headers as $index => $label) {
            $cell = chr(65 + $index).$headerRow;
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        $row = $headerRow + 1;
        foreach ($rows as $dataRow) {
            $sheet->setCellValue('A'.$row, $dataRow['quantity']);
            $sheet->setCellValue('B'.$row, 'N/A');
            $sheet->setCellValue('C'.$row, $dataRow['description']);
            $sheet->setCellValue('D'.$row, $dataRow['reference']);
            $sheet->setCellValue('E'.$row, $dataRow['utilization']);
            $sheet->setCellValue('F'.$row, $dataRow['location']);
            $row++;
        }

        $lastRow = max($row - 1, $headerRow);
        $sheet->getStyle('A'.$headerRow.':F'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'FO-AD-44-Solicitud-'.$purchaseRequest->folio().'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
