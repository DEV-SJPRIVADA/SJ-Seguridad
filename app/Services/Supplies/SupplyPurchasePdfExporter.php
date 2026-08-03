<?php

namespace App\Services\Supplies;

use App\Models\SupplyRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplyPurchasePdfExporter
{
    public function __construct(
        private readonly SupplyPurchaseReportExporter $excelExporter,
    ) {}

    public function generate(SupplyRequest $supplyRequest): string
    {
        $supplyRequest->loadMissing(['user', 'items.product', 'qualityReviewer', 'purchasingManager']);

        $lineItems = $this->excelExporter
            ->buildMergedRowsForRequest($supplyRequest)
            ->values()
            ->all();

        return Pdf::loadView('pdf.supply-request-solicitud', [
            'supplyRequest' => $supplyRequest,
            'lineItems' => $lineItems,
            'generatedAt' => now(),
            'formCode' => config('supplies.report.form_code'),
            'formVersion' => config('supplies.report.version'),
            'reportTitle' => config('supplies.report.title'),
        ])
            ->setPaper('letter', 'portrait')
            ->output();
    }

    public function filename(SupplyRequest $supplyRequest): string
    {
        return 'Suministro-'.$supplyRequest->folio().'.pdf';
    }
}
