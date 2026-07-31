<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseRequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PurchaseEmailApprovalController extends Controller
{
    /**
     * Enlaces legacy del correo (URLs firmadas) redirigen al detalle autenticado en la plataforma.
     */
    public function show(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->resolveDirector($request, $purchaseRequest);

        return redirect()->route('purchase-requests.show', [
            'module' => $purchaseRequest->area_key,
            'purchase_request' => $purchaseRequest->id,
        ]);
    }

    public function pdf(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestPdfService $pdfService): Response
    {
        $this->resolveDirector($request, $purchaseRequest);

        return response($pdfService->generate($purchaseRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfService->filename($purchaseRequest).'"',
        ]);
    }

    /**
     * La autorizacion ya no se realiza por correo; redirige al flujo en la plataforma.
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->resolveDirector($request, $purchaseRequest);

        return redirect()
            ->route('purchase-requests.show', [
                'module' => $purchaseRequest->area_key,
                'purchase_request' => $purchaseRequest->id,
            ])
            ->with('warning', 'La autorizacion debe realizarse desde la plataforma, en Pendientes de autorizacion.');
    }

    private function resolveDirector(Request $request, PurchaseRequest $purchaseRequest): User
    {
        abort_unless($request->hasValidSignature(), 403);

        $directorId = (int) $request->query('director', $request->input('director'));
        abort_unless($directorId > 0 && (int) $purchaseRequest->aprobador_id === $directorId, 403);

        return User::query()->whereKey($directorId)->firstOrFail();
    }
}
