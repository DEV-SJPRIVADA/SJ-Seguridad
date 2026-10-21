<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderPdfExporter
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(PurchaseOrder $order): array
    {
        $order->loadDocumentRelations();
        $lines = $order->items->count();
        $density = $this->density($lines);

        return [
            'order' => $order,
            'generatedAt' => now(),
            'formCode' => config('purchase-orders.form_code', 'FO-AD-07'),
            'formVersion' => config('purchase-orders.form_version', '04'),
            'formPeriod' => config('purchase-orders.form_period', 'Enero de 2025'),
            'reportTitle' => config('purchase-orders.report_title', 'ORDEN DE COMPRA O SERVICIO'),
            'company' => config('purchase-orders.company', 'SJ Seguridad'),
            'pageLabel' => 'Página 1 de 1',
            'condiciones' => (string) config('purchase-orders.condiciones', ''),
            'logoDataUri' => $this->logoDataUri(),
            'itemFontSize' => $density['font'],
            'itemCellPadding' => $density['pad'],
            'signatureHeight' => $density['sign'],
            'obsHeight' => $density['obs'],
        ];
    }

    public function html(PurchaseOrder $order): string
    {
        return view('pdf.purchase-order', $this->viewData($order))->render();
    }

    public function generate(PurchaseOrder $order): string
    {
        return Pdf::loadHTML($this->html($order))
            ->setPaper('letter', 'portrait')
            ->output();
    }

    public function pageCount(PurchaseOrder $order): int
    {
        $pdf = Pdf::loadHTML($this->html($order))->setPaper('letter', 'portrait');
        $pdf->render();

        return (int) $pdf->getDomPDF()->getCanvas()->get_page_count();
    }

    public function filename(PurchaseOrder $order): string
    {
        return 'FO-AD-07-OC-'.$order->numeroOc.'.pdf';
    }

    /**
     * Con muchas líneas se achica la tabla antes de saltar de página.
     *
     * @return array{font: string, pad: string, sign: string, obs: string}
     */
    private function density(int $lines): array
    {
        if ($lines >= 12) {
            return ['font' => '6px', 'pad' => '1px 2px', 'sign' => '16px', 'obs' => '12px'];
        }

        if ($lines >= 8) {
            return ['font' => '6.5px', 'pad' => '1px 2px', 'sign' => '20px', 'obs' => '14px'];
        }

        return ['font' => '7.5px', 'pad' => '2px 3px', 'sign' => '26px', 'obs' => '16px'];
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('images/logoSj.png');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($contents);
    }
}
