<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseTaxRate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Formulario FO-AD-07 (no es un listado). Mismos bloques que el PDF, incluido el texto legal.
 */
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

    public function buildSpreadsheet(PurchaseOrder $order): Spreadsheet
    {
        $order->loadDocumentRelations();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orden de compra');

        $this->buildHeader($sheet, $order);
        $this->buildAssociate($sheet, $order);
        $totalRow = $this->buildItems($sheet, $order);
        $obsRow = $totalRow + 1;
        $this->buildObservations($sheet, $order, $obsRow);
        $this->buildSignatures($sheet, $order, $obsRow + 3);
        $this->buildCondiciones($sheet, $obsRow + 7);

        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LETTER);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(1);

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
                $drawing->setHeight(46);
                $drawing->setCoordinates('A1');
                $drawing->setWorksheet($sheet);
            } catch (Throwable) {
                // Sin logo si el archivo no es legible.
            }
        }

        $sheet->mergeCells('A1:A4');
        $sheet->mergeCells('B1:F4');
        $sheet->setCellValue('B1', (string) config('purchase-orders.report_title', 'ORDEN DE COMPRA O SERVICIO')
            .($order->isAnulada() ? ' — ANULADA' : ''));
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $meta = [
            'G1' => (string) config('purchase-orders.form_code', 'FO-AD-07'),
            'G2' => (string) config('purchase-orders.form_period', 'Enero de 2025'),
            'G3' => 'Versión '.config('purchase-orders.form_version', '04'),
            'G4' => 'Página 1 de 1',
        ];

        foreach ($meta as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        foreach (range(1, 4) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        $sheet->getStyle('A1:G4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue('A5', 'Fecha(dd/mm/aaaa)');
        $sheet->mergeCells('B5:E5');
        $sheet->setCellValue('B5', $order->fechaImpresa());
        $sheet->setCellValue('F5', 'OC No.');
        $sheet->setCellValue('G5', $order->numeroOc);

        $sheet->setCellValue('A6', 'Ciudad');
        $sheet->mergeCells('B6:G6');
        $sheet->setCellValue('B6', (string) $order->ciudad);

        $this->labelStyle($sheet, 'A5');
        $this->labelStyle($sheet, 'F5');
        $this->labelStyle($sheet, 'A6');
        $sheet->getStyle('A5:G6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function buildAssociate(Worksheet $sheet, PurchaseOrder $order): void
    {
        $sheet->mergeCells('A7:G7');
        $sheet->setCellValue('A7', 'Información del Asociado de Negocio');
        $sheet->getStyle('A7')->getFont()->setBold(true);
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD6E3F0');

        $rows = [
            8 => ['Razón social', $order->provider_nombre],
            9 => ['NIT', $order->provider_identificacion],
            10 => ['Contacto', $order->provider_contacto],
            11 => ['Teléfono', $order->provider_telefono],
            12 => ['Correo electrónico', $order->provider_correo],
            13 => ['Dirección', $order->provider_direccion],
            14 => ['Ciudad', $order->provider_ciudad],
            15 => ['Forma de pago', $order->formaPagoImpresa()],
            16 => ['Asume el cliente', $order->banderaImpresa('asume_cliente')],
            17 => ['Proyecto nuevo', $order->banderaImpresa('proyecto_nuevo')],
            18 => ['Reinversión', $order->banderaImpresa('reinversion')],
            19 => ['Comodato', $order->banderaImpresa('comodato')],
            20 => ['Activo', $order->banderaImpresa('activo')],
        ];

        foreach ($rows as $row => [$label, $value]) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->mergeCells('B'.$row.':G'.$row);
            $sheet->setCellValue('B'.$row, $value ?? '');
            $this->labelStyle($sheet, 'A'.$row);
        }

        $sheet->getStyle('A7:G20')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function buildItems(Worksheet $sheet, PurchaseOrder $order): int
    {
        $sheet->mergeCells('A21:G21');
        $sheet->setCellValue('A21', 'Sírvase suministrar los servicios y/o artículos descritos a continuación:');
        $sheet->getStyle('A21')->getFont()->setBold(true);
        $sheet->getStyle('A21')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A21')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD6E3F0');

        $headers = [
            'A22' => 'Cantidad',
            'B22' => 'Artículo',
            'C22' => 'Descripción',
            'E22' => 'Valor unidad',
            'F22' => 'IVA',
            'G22' => 'Valor total',
        ];

        $sheet->mergeCells('C22:D22');

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('FF000000');
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD6E3F0');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $row = 23;

        foreach ($order->items as $item) {
            $sheet->mergeCells('C'.$row.':D'.$row);
            $sheet->setCellValue('A'.$row, (float) $item->cantidad);
            $sheet->setCellValue('B'.$row, $item->articulo);
            $sheet->setCellValue('C'.$row, $item->descripcion);
            $sheet->setCellValue('E'.$row, (float) $item->valor_unidad);
            $sheet->setCellValue('F'.$row, (float) $item->iva);
            $sheet->setCellValue('G'.$row, (float) $item->valor_total);
            $sheet->getStyle('E'.$row.':G'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');

            // El IVA sigue siendo número; si tiene tasa del catálogo se muestra al lado: $19.00 (19%).
            if ($item->iva_porcentaje !== null) {
                $sheet->getStyle('F'.$row)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00" ('.PurchaseTaxRate::formatPorcentaje($item->iva_porcentaje).')"');
            }

            $row++;
        }

        $sheet->mergeCells('A'.$row.':F'.$row);
        $sheet->setCellValue('A'.$row, 'TOTAL');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('G'.$row, (float) $order->total);
        $sheet->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true);
        $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');

        $sheet->getStyle('A21:G'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('C23:D'.$row)->getAlignment()->setWrapText(true);

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(18);

        return $row;
    }

    private function buildObservations(Worksheet $sheet, PurchaseOrder $order, int $row): void
    {
        $sheet->mergeCells('A'.$row.':G'.$row);
        $sheet->setCellValue('A'.$row, 'OBSERVACIONES');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD6E3F0');

        $body = $row + 1;
        $sheet->mergeCells('A'.$body.':G'.$body);
        $sheet->setCellValue('A'.$body, (string) $order->observaciones);
        $sheet->getStyle('A'.$body)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getRowDimension($body)->setRowHeight(28);
        $sheet->getStyle('A'.$row.':G'.$body)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function buildSignatures(Worksheet $sheet, PurchaseOrder $order, int $row): void
    {
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->mergeCells('C'.$row.':D'.$row);
        $sheet->mergeCells('E'.$row.':G'.$row);
        $sheet->setCellValue('A'.$row, 'Elaborado por:');
        $sheet->setCellValue('C'.$row, 'Revisado y aprobado por:');
        $sheet->setCellValue('E'.$row, 'Aprobado 2 por:');

        $space = $row + 1;
        $sheet->mergeCells('A'.$space.':B'.$space);
        $sheet->mergeCells('C'.$space.':D'.$space);
        $sheet->mergeCells('E'.$space.':F'.$space);
        $sheet->setCellValue('A'.$space, $order->elaboradoNombre());
        $sheet->setCellValue('C'.$space, $order->revisorNombre());
        $sheet->getRowDimension($space)->setRowHeight(36);
        $sheet->getStyle('A'.$space.':G'.$space)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_BOTTOM);

        $roles = $row + 2;
        $sheet->mergeCells('A'.$roles.':B'.$roles);
        $sheet->mergeCells('C'.$roles.':D'.$roles);
        $sheet->mergeCells('E'.$roles.':F'.$roles);
        $sheet->setCellValue('A'.$roles, 'Analista de Compras');
        $sheet->setCellValue('C'.$roles, 'Director(a) área solicitante');
        $sheet->setCellValue('E'.$roles, 'Director Admon. y Financiero');
        $sheet->setCellValue('G'.$roles, 'Gerente (cuando aplique)');
        $sheet->getStyle('A'.$row.':G'.$roles)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row.':G'.$roles)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A'.$row.':G'.$roles)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function buildCondiciones(Worksheet $sheet, int $row): void
    {
        $sheet->mergeCells('A'.$row.':G'.($row + 8));
        $sheet->setCellValue('A'.$row, (string) config('purchase-orders.condiciones', ''));
        $sheet->getStyle('A'.$row)->getFont()->setSize(6);
        $sheet->getStyle('A'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    private function labelStyle(Worksheet $sheet, string $cell): void
    {
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE7EEF7');
    }
}
