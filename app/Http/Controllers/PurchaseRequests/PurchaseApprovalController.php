<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequests\PurchaseApprovalService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseApprovalController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module, Request $request): View
    {
        $estado = $request->string('estado')->toString();
        if ($estado === '') {
            $estado = PurchaseRequest::ESTADO_PENDIENTE;
        }

        $query = PurchaseRequest::query()
            ->with(['user', 'items', 'aprobador'])
            ->where('aprobador_id', auth()->id());

        if ($estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $purchaseRequests = $query
            ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [PurchaseRequest::ESTADO_PENDIENTE])
            ->latest('fecha_solicitud')
            ->latest('id')
            ->get();

        // Anulaciones de OC que Compras envió a este director.
        $pendingCancellations = PurchaseOrder::query()
            ->with(['source', 'anulacionSolicitadaPor'])
            ->where('estado', PurchaseOrder::ESTADO_ANULACION_PENDIENTE)
            ->where('anulacion_aprobador_id', auth()->id())
            ->orderBy('anulacion_solicitada_at')
            ->get();

        // Subpestañas de Autorizaciones compras: solicitudes de compra o anulaciones de órdenes.
        $vista = $request->string('vista')->toString() === 'anulaciones' ? 'anulaciones' : 'solicitudes';

        return view('modules.purchase-requests.approval.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'vista' => $vista,
            'approvalTabs' => [
                'solicitudes' => [
                    'label' => 'Pendientes de autorización',
                    'url' => route('purchase-requests.approval.index', ['module' => $module]),
                ],
                'anulaciones' => [
                    'label' => 'Anulaciones de órdenes',
                    'url' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'anulaciones']),
                    'count' => $pendingCancellations->count(),
                ],
            ],
            'purchaseRequests' => $purchaseRequests,
            'pendingCancellations' => $pendingCancellations,
            'filters' => [
                'estado' => $estado,
            ],
            'estadoLabels' => PurchaseRequest::estadosLabels(),
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
                'web',
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['estado' => $exception->getMessage()]);
        }

        return redirect()
            ->route('purchase-requests.approval.index', ['module' => $module])
            ->with('status', 'Solicitud N.º '.$purchaseRequest->folioVisible().' actualizada.');
    }
}
