<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseApprovalRequest;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequests\PurchaseApprovalService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseApprovalController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module): View
    {
        $purchaseRequests = PurchaseRequest::query()
            ->with(['user', 'items', 'aprobador'])
            ->where('estado', PurchaseRequest::ESTADO_PENDIENTE)
            ->where('aprobador_id', auth()->id())
            ->latest()
            ->get();

        return view('modules.purchase-requests.approval.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequests' => $purchaseRequests,
        ]);
    }

    public function update(
        UpdatePurchaseApprovalRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseApprovalService $approvalService,
    ): RedirectResponse {
        try {
            $approvalService->resolve(
                $purchaseRequest,
                $request->validated('estado'),
                (int) auth()->id(),
                $request->validated('comentarios_director'),
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['estado' => $exception->getMessage()]);
        }

        return redirect()
            ->route('purchase-requests.approval.index', ['module' => $module])
            ->with('status', 'Solicitud N.º '.$purchaseRequest->folio().' actualizada.');
    }
}
