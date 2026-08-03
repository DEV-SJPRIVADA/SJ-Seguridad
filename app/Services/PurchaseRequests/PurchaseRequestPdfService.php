<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PurchaseRequestPdfService
{
    public function generate(PurchaseRequest $purchaseRequest): string
    {
        $purchaseRequest->loadMissing(['user', 'aprobador', 'items', 'procesadoComprasPor']);

        $itemPhotos = [];
        foreach ($purchaseRequest->items as $item) {
            $itemPhotos[$item->id] = $this->fotoAsDataUri($item->foto_path);
        }

        return Pdf::loadView('pdf.purchase-request-solicitud', [
            'purchaseRequest' => $purchaseRequest,
            'itemPhotos' => $itemPhotos,
            'generatedAt' => now(),
            'formCode' => config('purchase-requests.form_code'),
            'formVersion' => config('purchase-requests.form_version'),
            'reportTitle' => config('purchase-requests.report_title'),
        ])
            ->setPaper('letter', 'portrait')
            ->output();
    }

    public function filename(PurchaseRequest $purchaseRequest): string
    {
        return 'Solicitud-'.$purchaseRequest->folio().'.pdf';
    }

    private function fotoAsDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        $mime = mime_content_type($absolute) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }
}
