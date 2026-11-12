<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseQuotation;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Entrega el archivo de una cotización para previsualizarlo (PDF o imagen) inline.
 * Lo usan Compras, el aprobador del lote y el aprobador de la OC.
 */
final class PurchaseQuotationFileService
{
    public function response(PurchaseQuotation $quotation): BinaryFileResponse
    {
        $disk = (string) config('purchase-requests.quotations.disk', 'local');
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($quotation->stored_path), 404);

        $filename = str_replace(['"', "\r", "\n"], '', $quotation->original_name);
        $mime = $quotation->mime_type ?: 'application/octet-stream';

        return response()->file($storage->path($quotation->stored_path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
