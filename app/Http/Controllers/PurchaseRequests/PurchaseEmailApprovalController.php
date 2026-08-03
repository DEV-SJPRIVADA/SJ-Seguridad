<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\EmailPurchaseApprovalRequest;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseApprovalService;
use App\Services\PurchaseRequests\PurchaseEmailApprovalUrlBuilder;
use App\Services\PurchaseRequests\PurchaseRequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PurchaseEmailApprovalController extends Controller
{
    public function show(
        Request $request,
        PurchaseRequest $purchaseRequest,
        PurchaseEmailApprovalUrlBuilder $urlBuilder,
    ): View {
        $director = $this->resolveDirector($request, $purchaseRequest);
        $purchaseRequest->load(['user', 'items']);

        $alreadyResolved = $purchaseRequest->estado !== PurchaseRequest::ESTADO_PENDIENTE;

        return view('modules.purchase-requests.email-approval', [
            'purchaseRequest' => $purchaseRequest,
            'director' => $director,
            'alreadyResolved' => $alreadyResolved,
            'decideUrl' => $urlBuilder->updateUrl($purchaseRequest, $director),
            'pdfUrl' => $urlBuilder->pdfUrl($purchaseRequest, $director),
        ]);
    }

    public function pdf(
        Request $request,
        PurchaseRequest $purchaseRequest,
        PurchaseRequestPdfService $pdfService,
    ): Response {
        $this->resolveDirector($request, $purchaseRequest);
        $purchaseRequest->load(['user', 'items', 'aprobador']);

        return response($pdfService->generate($purchaseRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfService->filename($purchaseRequest).'"',
        ]);
    }

    public function update(
        EmailPurchaseApprovalRequest $request,
        PurchaseRequest $purchaseRequest,
        PurchaseApprovalService $approvalService,
        PurchaseEmailApprovalUrlBuilder $urlBuilder,
    ): View|RedirectResponse {
        $director = $this->resolveDirector($request, $purchaseRequest);
        $purchaseRequest->load(['user', 'items']);

        if ($purchaseRequest->estado !== PurchaseRequest::ESTADO_PENDIENTE) {
            return view('modules.purchase-requests.email-approval', [
                'purchaseRequest' => $purchaseRequest,
                'director' => $director,
                'alreadyResolved' => true,
                'decideUrl' => $urlBuilder->updateUrl($purchaseRequest, $director),
                'pdfUrl' => $urlBuilder->pdfUrl($purchaseRequest, $director),
            ]);
        }

        try {
            $approvalService->resolve(
                $purchaseRequest,
                $request->validated('estado'),
                $director->id,
                $request->validated('comentarios_director'),
            );
        } catch (\InvalidArgumentException $exception) {
            return redirect($urlBuilder->showUrl($purchaseRequest, $director))
                ->withErrors(['estado' => $exception->getMessage()])
                ->withInput();
        }

        $purchaseRequest->refresh();
        $purchaseRequest->load(['user', 'items']);

        return view('modules.purchase-requests.email-approval-result', [
            'purchaseRequest' => $purchaseRequest,
            'estado' => $request->validated('estado'),
        ]);
    }

    private function resolveDirector(Request $request, PurchaseRequest $purchaseRequest): User
    {
        abort_unless($request->hasValidSignature(), 403);

        $directorId = (int) $request->query('director', $request->input('director'));
        abort_unless($directorId > 0 && (int) $purchaseRequest->aprobador_id === $directorId, 403);

        return User::query()->whereKey($directorId)->firstOrFail();
    }
}
