<?php

namespace App\Services\Supplies;

use App\Models\SupplyRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
            ->map(function (array $row): array {
                $row['photo_data_uri'] = $this->fotoAsDataUri($row['foto_path'] ?? null);

                return $row;
            })
            ->all();

        return Pdf::loadView('pdf.supply-request-solicitud', [
            'supplyRequest' => $supplyRequest,
            'lineItems' => $lineItems,
            'generatedAt' => now(),
            'formCode' => config('supplies.report.form_code'),
            'formVersion' => config('supplies.report.version'),
            'formPeriod' => config('supplies.report.period', 'Marzo 2026'),
            'photoPlaceholder' => config('supplies.report.photo_placeholder', 'S/A'),
            'reportTitle' => config('supplies.report.title'),
            'pageLabel' => 'Página 1 de 1',
        ])
            ->setPaper('letter', 'portrait')
            ->output();
    }

    public function filename(SupplyRequest $supplyRequest): string
    {
        return 'Suministro-'.$supplyRequest->folio().'.pdf';
    }

    private function fotoAsDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            $absolute = Storage::disk('public')->path($path);
            if (! is_file($absolute)) {
                return null;
            }

            $mime = mime_content_type($absolute) ?: 'image/jpeg';
            $binary = file_get_contents($absolute);
            if ($binary === false || $binary === '') {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($binary);
        } catch (Throwable) {
            return null;
        }
    }
}
