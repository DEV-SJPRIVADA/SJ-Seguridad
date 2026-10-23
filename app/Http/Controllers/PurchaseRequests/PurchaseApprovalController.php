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
        $userId = (int) auth()->id();

        // Subpestañas de Autorizaciones compras: solicitudes, aprobación de órdenes o anulaciones de órdenes.
        $vista = $request->string('vista')->toString();
        $vista = in_array($vista, ['ordenes', 'anulaciones'], true) ? $vista : 'solicitudes';

        // Filtro "Vista" de cada subpestaña: por defecto pendientes; el resto es historial.
        $estado = $request->string('estado')->toString();
        $estadosValidos = $vista === 'solicitudes'
            ? [PurchaseRequest::ESTADO_PENDIENTE, 'todos', PurchaseRequest::ESTADO_APROBADO, PurchaseRequest::ESTADO_RECHAZADO]
            : [PurchaseOrder::APROBACION_PENDIENTE, 'todos', PurchaseOrder::APROBACION_APROBADA, PurchaseOrder::APROBACION_RECHAZADA];
        if (! in_array($estado, $estadosValidos, true)) {
            $estado = PurchaseRequest::ESTADO_PENDIENTE;
        }

        $purchaseRequests = collect();
        $orderApprovals = collect();
        $cancellations = collect();

        if ($vista === 'solicitudes') {
            $query = PurchaseRequest::query()
                ->with(['user', 'items', 'aprobador'])
                ->where('aprobador_id', $userId);

            if ($estado !== 'todos') {
                $query->where('estado', $estado);
            }

            $purchaseRequests = $query
                ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [PurchaseRequest::ESTADO_PENDIENTE])
                ->latest('fecha_solicitud')
                ->latest('id')
                ->get();
        } elseif ($vista === 'ordenes') {
            // OC que Compras envió a este aprobador (director o administrador); las retiradas no quedan asignadas.
            $query = PurchaseOrder::query()
                ->with(['source', 'aprobacionSolicitadaPor'])
                ->where('aprobacion_aprobador_id', $userId);

            $estado === 'todos'
                ? $query->whereNotNull('aprobacion_estado')
                : $query->where('aprobacion_estado', $estado);

            $orderApprovals = $query
                ->orderByRaw('CASE WHEN aprobacion_estado = ? THEN 0 ELSE 1 END', [PurchaseOrder::APROBACION_PENDIENTE])
                ->orderByDesc('aprobacion_resuelta_at')
                ->orderByDesc('aprobacion_solicitada_at')
                ->get();
        } else {
            // Anulaciones de OC que Compras envió a este director: pendientes o ya resueltas por él.
            $query = PurchaseOrder::query()
                ->with(['source', 'anulacionSolicitadaPor'])
                ->where('anulacion_aprobador_id', $userId);

            match ($estado) {
                PurchaseOrder::APROBACION_PENDIENTE => $query->where('estado', PurchaseOrder::ESTADO_ANULACION_PENDIENTE),
                'todos' => $query->where(fn ($q) => $q
                    ->where('estado', PurchaseOrder::ESTADO_ANULACION_PENDIENTE)
                    ->orWhereNotNull('anulacion_resultado')),
                default => $query->where('anulacion_resultado', $estado),
            };

            $cancellations = $query
                ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [PurchaseOrder::ESTADO_ANULACION_PENDIENTE])
                ->orderByDesc('anulacion_resuelta_at')
                ->orderByDesc('anulacion_solicitada_at')
                ->get();
        }

        // Contadores de las subpestañas: siempre lo pendiente, sin importar el filtro activo.
        $pendingOrderApprovalsCount = PurchaseOrder::query()
            ->where('aprobacion_estado', PurchaseOrder::APROBACION_PENDIENTE)
            ->where('aprobacion_aprobador_id', $userId)
            ->count();
        $pendingCancellationsCount = PurchaseOrder::query()
            ->where('estado', PurchaseOrder::ESTADO_ANULACION_PENDIENTE)
            ->where('anulacion_aprobador_id', $userId)
            ->count();

        return view('modules.purchase-requests.approval.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'vista' => $vista,
            'orderApprovals' => $orderApprovals,
            'approvalTabs' => [
                'solicitudes' => [
                    'label' => 'Solicitudes de Compra',
                    'url' => route('purchase-requests.approval.index', ['module' => $module]),
                ],
                'ordenes' => [
                    'label' => 'Órdenes de Compra',
                    'url' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'ordenes']),
                    'count' => $pendingOrderApprovalsCount,
                ],
                'anulaciones' => [
                    'label' => 'Anulaciones de órdenes',
                    'url' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'anulaciones']),
                    'count' => $pendingCancellationsCount,
                ],
            ],
            'purchaseRequests' => $purchaseRequests,
            'cancellations' => $cancellations,
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
