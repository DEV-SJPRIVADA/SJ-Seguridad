<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderPdfExporter
{
    public function generate(PurchaseOrder $order): string
    {
        $order->loadMissing(['items', 'provider', 'source']);

        return Pdf::loadView('pdf.purchase-order', [
            'order' => $order,
            'generatedAt' => now(),
            'formCode' => config('purchase-orders.form_code', 'FO-AD-07'),
            'formVersion' => config('purchase-orders.form_version', '04'),
            'formPeriod' => config('purchase-orders.form_period', 'Enero 2025'),
            'reportTitle' => config('purchase-orders.report_title', 'ORDEN DE COMPRA'),
            'company' => config('purchase-orders.company', 'SJ Seguridad'),
            'pageLabel' => 'Página 1 de 1',
        ])
            ->setPaper('letter', 'portrait')
            ->output();
    }

    public function filename(PurchaseOrder $order): string
    {
        return 'FO-AD-07-OC-'.$order->numeroOc.'.pdf';
    }
}
