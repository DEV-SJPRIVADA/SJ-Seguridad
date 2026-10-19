<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PurchaseOrderExcelExporter
{
    public function toDownloadResponse(PurchaseOrder $order): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($order);

        $filename = 'FO-AD-07-OC-'.$order->numeroOc.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Construye el libro FO-AD-07 de la orden de compra.
     */
    public function buildSpreadsheet(PurchaseOrder $order): Spreadsheet
    {
        $order->loadMissing(['items', 'source']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orden de compra');

        $this->buildHeader($sheet, $order);
        $itemsHeaderRow = $this->buildProviderBlock($sheet, $order);
        $afterTotalsRow = $this->buildItems($sheet, $order, $itemsHeaderRow);
        $this->buildSignatures($sheet, $afterTotalsRow + 3);

        return $spreadsheet;
    }

    private function buildHeader(Worksheet $sheet, PurchaseOrder $order): void
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
        $sheet->setCellValue('B1', (string) config('purchase-orders.report_title', 'ORDEN DE COMPRA'));
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $meta = [
            'G1' => (string) config('purchase-orders.form_code', 'FO-AD-07'),
            'G2' => (string) config('purchase-orders.form_period', 'Enero 2025'),
            'G3' => 'Versión '.config('purchase-orders.form_version', '04'),
            'G4' => 'Página 1 de 1',
        ];

        foreach ($meta as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->setCellValue('A5', 'Orden de compra N.º: '.$order->numeroOc);
        $sheet->getStyle('A5')->getFont()->setBold(true)->setItalic(true);

        $sheet->setCellValue('A6', 'Fecha: '.(string) $order->fecha);
        $sheet->setCellValue('C6', 'Origen: '.$order->sourceLabel().' N.º '.$order->sourceFolio());
        $sheet->setCellValue('F6', 'Estado: '.$order->estadoLabel());

        $this->section($sheet, 'A8', 'Proveedor');
    }

    private function buildProviderBlock(Worksheet $sheet, PurchaseOrder $order): int
    {
        $rows = [
            ['Identificación', $order->provider_identificacion, 'Nombre', $order->provider_nombre],
            ['Contacto', $order->provider_contacto ?? '—', 'Teléfono', $order->provider_telefono ?? '—'],
            ['Correo', $order->provider_correo ?? '—', 'Ciudad', $order->provider_ciudad ?? '—'],
            ['Dirección', $order->provider_direccion ?? '—', 'Forma de pago', $order->provider_forma_pago ?? '—'],
            ['Días de pago', $order->provider_dias_pago !== null ? (string) $order->provider_dias_pago : '—', 'Ciudad de entrega', $order->ciudad ?? '—'],
        ];

        $row = 9;
        foreach ($rows as $data) {
            $sheet->setCellValue('A'.$row, $data[0]);
            $sheet->mergeCells('B'.$row.':C'.$row);
            $sheet->setCellValue('B'.$row, $data[1]);
            $sheet->setCellValue('D'.$row, $data[2]);
            $sheet->mergeCells('E'.$row.':G'.$row);
            $sheet->setCellValue('E'.$row, $data[3]);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->getStyle('D'.$row)->getFont()->setBold(true);
            $row++;
        }

        $sheet->getStyle('A9:G'.($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A9:G'.($row - 1))->getAlignment()->setWrapText(true);

        if ($order->observaciones) {
            $sheet->setCellValue('A'.$row, 'Observaciones');
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->mergeCells('B'.$row.':G'.$row);
            $sheet->setCellValue('B'.$row, $order->observaciones);
            $sheet->getStyle('B'.$row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A'.$row.':G'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        $this->section($sheet, 'A'.($row + 1), 'Detalle');

        return $row + 3;
    }

    private function buildItems(Worksheet $sheet, PurchaseOrder $order, int $headerRow): int
    {
        $headers = ['#', 'Cantidad', 'Artículo', 'Descripción', 'Valor unidad', 'IVA', 'Valor total'];

        foreach ($headers as $index => $label) {
            $cell = chr(65 + $index).$headerRow;
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $row = $headerRow + 1;
        $subtotal = 0.0;
        $ivaTotal = 0.0;

        foreach ($order->items as $item) {
            $lineSubtotal = (float) $item->cantidad * (float) $item->valor_unidad;
            $subtotal += $lineSubtotal;
            $ivaTotal += (float) $item->iva;

            $sheet->setCellValue('A'.$row, $item->orden);
            $sheet->setCellValue('B'.$row, (float) $item->cantidad);
            $sheet->setCellValue('C'.$row, $item->articulo);
            $sheet->setCellValue('D'.$row, $item->descripcion);
            $sheet->setCellValue('E'.$row, (float) $item->valor_unidad);
            $sheet->setCellValue('F'.$row, (float) $item->iva);
            $sheet->setCellValue('G'.$row, (float) $item->valor_total);
            $sheet->getStyle('A'.$row.':G'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row++;
        }

        $lastItemRow = max($row - 1, $headerRow);
        $sheet->getStyle('A'.$headerRow.':G'.$lastItemRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('E'.$headerRow.':G'.$lastItemRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('D'.$row, 'Subtotal');
        $sheet->setCellValue('E'.$row, $subtotal);
        $sheet->setCellValue('D'.($row + 1), 'IVA');
        $sheet->setCellValue('E'.($row + 1), $ivaTotal);
        $sheet->setCellValue('D'.($row + 2), 'TOTAL');
        $sheet->setCellValue('G'.($row + 2), (float) $order->total);
        $sheet->getStyle('D'.$row.':G'.($row + 2))->getFont()->setBold(true);
        $sheet->getStyle('E'.$row.':G'.($row + 2))->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(42);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(18);

        return $row + 3;
    }

    private function buildSignatures(Worksheet $sheet, int $row): void
    {

        $labels = ['Elaboró', 'Aprobó', 'Recibió'];
        foreach ($labels as $index => $label) {
            $column = chr(65 + ($index * 2));
            $sheet->mergeCells($column.$row.':'.chr(66 + ($index * 2)).$row);
            $sheet->setCellValue($column.$row, $label);
            $sheet->getStyle($column.$row)->getFont()->setBold(true);
            $sheet->getStyle($column.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($column.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        }
    }

    private function section(Worksheet $sheet, string $cell, string $title): void
    {
        $column = preg_replace('/\d+/', '', $cell);
        $row = (int) preg_replace('/\D+/', '', $cell);

        $sheet->setCellValue($cell, $title);
        $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FF003366');
        $sheet->mergeCells($column.$row.':G'.$row);
        $sheet->getStyle($column.$row.':G'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    }
}
