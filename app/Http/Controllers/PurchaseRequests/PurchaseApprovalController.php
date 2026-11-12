<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequests\PurchaseApprovalService;
use App\Services\PurchaseRequests\PurchaseQuotationBatchService;
use App\Support\PurchaseQuotationBatch;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseApprovalController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module, Request $request, PurchaseQuotationBatchService $quotationBatchService): View
    {
        $userId = (int) auth()->id();

        // Subpestañas de Autorizaciones compras: solicitudes, cotizaciones, órdenes o anulaciones.
        $vista = $request->string('vista')->toString();
        $vista = in_array($vista, ['cotizaciones', 'ordenes', 'anulaciones'], true) ? $vista : 'solicitudes';

        // Filtro "Vista" de cada subpestaña: por defecto pendientes; el resto es historial.
        $estado = $request->string('estado')->toString();
        $estadosValidos = match ($vista) {
            'cotizaciones' => [PurchaseQuotation::ESTADO_PENDIENTE, 'todos', PurchaseQuotation::ESTADO_APROBADA, PurchaseQuotation::ESTADO_RECHAZADA],
            'solicitudes' => [PurchaseRequest::ESTADO_PENDIENTE, 'todos', PurchaseRequest::ESTADO_APROBADO, PurchaseRequest::ESTADO_RECHAZADO],
            default => [PurchaseOrder::APROBACION_PENDIENTE, 'todos', PurchaseOrder::APROBACION_APROBADA, PurchaseOrder::APROBACION_RECHAZADA],
        };
        if (! in_array($estado, $estadosValidos, true)) {
            $estado = $vista === 'cotizaciones' ? PurchaseQuotation::ESTADO_PENDIENTE : PurchaseRequest::ESTADO_PENDIENTE;
        }

        $purchaseRequests = collect();
        $quotationBatches = collect();
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
        } elseif ($vista === 'cotizaciones') {
            // Lotes que Compras envió a este aprobador; las cotizaciones retiradas vuelven a Cargada.
            $quotationBatches = $quotationBatchService
                ->forApprover($request->user())
                ->when($estado !== 'todos', fn ($batches) => $batches->filter(
                    fn (PurchaseQuotationBatch $batch): bool => $batch->estado() === $estado,
                ))
                ->sortByDesc(fn (PurchaseQuotationBatch $batch): int => $batch->estaPendiente() ? 1 : 0)
                ->values();
        } elseif ($vista === 'ordenes') {
            // OC que Compras envió a este aprobador (director o administrador); las retiradas no quedan asignadas.
            // Se carga la cotización aprobada vinculada (si existe) para mostrarla en el listado.
            $query = PurchaseOrder::query()
                ->with(['source', 'aprobacionSolicitadaPor', 'purchaseQuotation.aprobador'])
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
        $pendingQuotationApprovalsCount = $quotationBatchService->pendingCountForApprover($request->user());

        return view('modules.purchase-requests.approval.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'vista' => $vista,
            'quotationBatches' => $quotationBatches,
            'orderApprovals' => $orderApprovals,
            'approvalTabs' => [
                'solicitudes' => [
                    'label' => 'Solicitudes de Compra',
                    'url' => route('purchase-requests.approval.index', ['module' => $module]),
                ],
                'cotizaciones' => [
                    'label' => 'Cotizaciones',
                    'url' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'cotizaciones']),
                    'count' => $pendingQuotationApprovalsCount,
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
