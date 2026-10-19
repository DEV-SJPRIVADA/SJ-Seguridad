<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
            'formPeriod' => config('purchase-requests.form_period', 'Marzo 2026'),
            'photoPlaceholder' => config('purchase-requests.photo_placeholder', 'S/A'),
            'reportTitle' => config('purchase-requests.report_title'),
            'pageLabel' => 'Página 1 de 1',
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
