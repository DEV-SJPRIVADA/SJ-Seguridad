<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseApprovalService;
use App\Services\PurchaseRequests\PurchaseRequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use InvalidArgumentException;

class PurchaseEmailApprovalController extends Controller
{
    public function show(Request $request, PurchaseRequest $purchaseRequest): View
    {
        $director = $this->resolveDirector($request, $purchaseRequest);

        $purchaseRequest->load(['user', 'items', 'aprobador']);

        $decideUrl = URL::temporarySignedRoute(
            'purchase-requests.email-approval.update',
            now()->addDays(config('purchase-requests.email_approval_link_days', 7)),
            ['purchase_request' => $purchaseRequest->id, 'director' => $director->id],
        );

        return view('modules.purchase-requests.email-approval', [
            'purchaseRequest' => $purchaseRequest,
            'director' => $director,
            'decideUrl' => $decideUrl,
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

    public function update(
        Request $request,
        PurchaseRequest $purchaseRequest,
        PurchaseApprovalService $approvalService,
    ): RedirectResponse {
        $director = $this->resolveDirector($request, $purchaseRequest);

        $estado = $request->input('estado');
        if (! in_array($estado, [PurchaseRequest::ESTADO_APROBADO, PurchaseRequest::ESTADO_RECHAZADO], true)) {
            return back()->withErrors(['estado' => 'Accion no valida.']);
        }

        try {
            $approvalService->resolve(
                $purchaseRequest,
                $estado,
                $director->id,
                $request->input('comentarios_director'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['estado' => $exception->getMessage()]);
        }

        return redirect()
            ->route('purchase-requests.email-approval.show', [
                'purchase_request' => $purchaseRequest->id,
                'director' => $director->id,
            ])
            ->with('status', 'Solicitud N.º '.$purchaseRequest->folio().' actualizada.');
    }

    private function resolveDirector(Request $request, PurchaseRequest $purchaseRequest): User
    {
        abort_unless($request->hasValidSignature(), 403);

        $directorId = (int) $request->query('director', $request->input('director'));
        abort_unless($directorId > 0 && (int) $purchaseRequest->aprobador_id === $directorId, 403);

        return User::query()->whereKey($directorId)->firstOrFail();
    }
}
