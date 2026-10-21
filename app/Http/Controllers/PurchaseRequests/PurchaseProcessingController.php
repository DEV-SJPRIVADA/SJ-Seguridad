<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ClosePurchaseBalanceRequest;
use App\Http\Requests\PurchaseRequests\ProcessSupplyComprasRequest;
use App\Http\Requests\PurchaseRequests\ReturnToRequesterRequest;
use App\Mail\SupplyRequestReturnedMail;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Services\Compras\ComprasQueueFilterBag;
use App\Services\Compras\ComprasQueueService;
use App\Services\PurchaseRequests\PurchaseFlowStateService;
use App\Services\PurchaseRequests\PurchaseRequestAuditLogService;
use App\Services\PurchaseRequests\PurchaseRequestNotificationService;
use App\Services\Supplies\SupplyPurchasePdfExporter;
use App\Services\Supplies\SupplyPurchaseReportExporter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use InvalidArgumentException;

class PurchaseProcessingController extends Controller
{
    public function index(string $module, Request $request, ComprasQueueService $queueService): View
    {
        $filters = ComprasQueueFilterBag::fromRequest($request);
        $queueResult = $queueService->resolve($filters);

        return view('modules.purchase-requests.processing.index', [
            'module' => $module,
            'queueItems' => $queueResult['items'],
            'queueTruncated' => $queueResult['truncated'],
            'queueTotalMatching' => $queueResult['total_matching'],
            'filters' => $filters->toViewArray(),
            'areas' => config('access.areas', []),
            'pedidosVistas' => [
                'por_comprar' => 'Por comprar',
                'con_orden' => 'Con orden',
                'historial' => 'Historial',
            ],
            'queueCount' => $queueResult['items']->count(),
        ]);
    }

    public function editPurchase(string $module, PurchaseRequest $purchaseRequest): View
    {
        Gate::authorize('process', $purchaseRequest);

        $purchaseRequest->load(['user', 'aprobador', 'items']);

        return view('modules.purchase-requests.processing.process-purchase', [
            'module' => $module,
            'purchaseRequest' => $purchaseRequest,
            'estadosCompras' => PurchaseRequest::estadosComprasLabels(),
        ]);
    }

    public function updatePurchase(string $module, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        Gate::authorize('process', $purchaseRequest);

        return redirect()
            ->route('purchase-requests.processing.purchase', [
                'module' => $module,
                'purchase_request' => $purchaseRequest->id,
            ])
            ->with('status', 'El estado lo calcula el sistema al emitir órdenes o al cerrar el saldo.');
    }

    public function closePurchase(
        ClosePurchaseBalanceRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseFlowStateService $flowState,
        PurchaseRequestAuditLogService $auditLogService,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);

        return $this->closeBalance(
            $request,
            $flowState,
            $auditLogService,
            $purchaseRequest,
            'estado_compras',
            $purchaseRequest->estado_compras,
            PurchaseRequest::COMPRAS_COMPLETADO,
            route('purchase-requests.processing.purchase', [
                'module' => $module,
                'purchase_request' => $purchaseRequest->id,
            ]),
            'Solicitud N.º '.$purchaseRequest->folioVisible().' cerrada.',
        );
    }

    /**
     * Devuelve la compra al solicitante. Al reenviarla vuelve a Por autorizar con el director.
     */
    public function returnPurchase(
        ReturnToRequesterRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseFlowStateService $flowState,
        PurchaseRequestAuditLogService $auditLogService,
        PurchaseRequestNotificationService $notifications,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);

        $motivo = (string) $request->validated('motivo_devolucion');
        $previous = ['estado' => $purchaseRequest->estado, 'estado_compras' => $purchaseRequest->estado_compras];

        try {
            $flowState->returnToRequester($purchaseRequest, $request->user(), $motivo);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['motivo_devolucion' => $exception->getMessage()])->withInput();
        }

        $purchaseRequest->refresh();

        $auditLogService->logModelChange(
            eventType: 'compras_processing',
            action: 'return_to_requester',
            model: $purchaseRequest,
            before: $previous,
            after: ['estado' => $purchaseRequest->estado, 'estado_compras' => $purchaseRequest->estado_compras],
            reason: $motivo,
            metadata: ['folio' => $purchaseRequest->folio()],
        );

        $notifications->notifyRequesterReturned($purchaseRequest, $motivo);

        return redirect()
            ->route('purchase-requests.processing.index', ['module' => $module])
            ->with('status', 'Solicitud N.º '.$purchaseRequest->folioVisible().' devuelta al solicitante.');
    }

    public function editSupply(string $module, SupplyRequest $supplyRequest): View|RedirectResponse
    {
        abort_unless(in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras', 'completada'], true), 403);

        $supplyRequest->load(['user', 'items.product', 'qualityReviewer', 'site']);

        return view('modules.purchase-requests.processing.process-supply', [
            'module' => $module,
            'supplyRequest' => $supplyRequest,
        ]);
    }

    public function updateSupply(
        ProcessSupplyComprasRequest $request,
        string $module,
        SupplyRequest $supplyRequest,
    ): RedirectResponse {
        abort_unless($supplyRequest->puedeCerrarSaldo(), 403);

        // No se escribe unit_cost: un costo histórico se conserva y la OC lo precarga como valor_unidad.
        $observations = $request->validated()['purchasing_observations'] ?? null;

        if (is_array($observations) && $observations !== []) {
            DB::transaction(function () use ($observations, $supplyRequest): void {
                foreach ($observations as $itemId => $text) {
                    $supplyRequest->items()->whereKey($itemId)->update([
                        'purchasing_observations' => $text,
                    ]);
                }
            });
        }

        return redirect()
            ->route('purchase-requests.processing.supply', [
                'module' => $module,
                'supply_request' => $supplyRequest->id,
            ])
            ->with('status', 'El precio del suministro '.$supplyRequest->folioVisible().' se registra al emitir la orden de compra. El estado no cambia hasta emitir una orden o cerrar el saldo.');
    }

    public function closeSupply(
        ClosePurchaseBalanceRequest $request,
        string $module,
        SupplyRequest $supplyRequest,
        PurchaseFlowStateService $flowState,
        PurchaseRequestAuditLogService $auditLogService,
    ): RedirectResponse {
        abort_unless($supplyRequest->puedeCerrarSaldo(), 403);

        return $this->closeBalance(
            $request,
            $flowState,
            $auditLogService,
            $supplyRequest,
            'status',
            $supplyRequest->status,
            'completada',
            route('purchase-requests.processing.supply', [
                'module' => $module,
                'supply_request' => $supplyRequest->id,
            ]),
            'Suministro '.$supplyRequest->folioVisible().' cerrado.',
        );
    }

    /**
     * Devuelve el suministro al solicitante. Al reenviarlo vuelve a Por autorizar con Calidad.
     */
    public function returnSupply(
        ReturnToRequesterRequest $request,
        string $module,
        SupplyRequest $supplyRequest,
        PurchaseFlowStateService $flowState,
        PurchaseRequestAuditLogService $auditLogService,
    ): RedirectResponse {
        $motivo = (string) $request->validated('motivo_devolucion');
        $previous = $supplyRequest->status;

        try {
            $flowState->returnToRequester($supplyRequest, $request->user(), $motivo);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['motivo_devolucion' => $exception->getMessage()])->withInput();
        }

        $supplyRequest->refresh();

        $auditLogService->logModelChange(
            eventType: 'supply_compras',
            action: 'return_to_requester',
            model: $supplyRequest,
            before: ['status' => $previous],
            after: ['status' => $supplyRequest->status],
            reason: $motivo,
            metadata: ['folio' => $supplyRequest->folio()],
        );

        $this->notifySupplyRequesterReturned($supplyRequest, $motivo);

        return redirect()
            ->route('purchase-requests.processing.index', ['module' => $module])
            ->with('status', 'Suministro '.$supplyRequest->folioVisible().' devuelto al solicitante.');
    }

    public function exportSupplyPdf(
        string $module,
        SupplyRequest $supplyRequest,
        SupplyPurchasePdfExporter $exporter,
        PurchaseRequestAuditLogService $auditLogService,
    ): Response {
        abort_unless(in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras', 'completada'], true), 403);

        $auditLogService->logEvent(
            eventType: 'export',
            action: 'supply_pdf',
            metadata: [
                'supply_request_id' => $supplyRequest->id,
            ],
            model: $supplyRequest,
        );

        return response($exporter->generate($supplyRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$exporter->filename($supplyRequest).'"',
        ]);
    }

    public function exportSupplyExcel(
        string $module,
        SupplyRequest $supplyRequest,
        SupplyPurchaseReportExporter $exporter,
        PurchaseRequestAuditLogService $auditLogService,
    ): Response {
        abort_unless(in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras', 'completada'], true), 403);

        $rows = $exporter->buildMergedRowsForRequest($supplyRequest);

        if (! $supplyRequest->exported_at) {
            $supplyRequest->update(['exported_at' => now()]);
        }

        $auditLogService->logEvent(
            eventType: 'export',
            action: 'supply_excel',
            metadata: [
                'supply_request_id' => $supplyRequest->id,
                'rows_exported' => count($rows),
            ],
            model: $supplyRequest,
        );

        return $exporter->toDownloadResponseForRequest($supplyRequest, $rows);
    }

    private function notifySupplyRequesterReturned(SupplyRequest $supplyRequest, string $motivo): void
    {
        $email = $supplyRequest->user?->email;

        if (! filled($email)) {
            return;
        }

        try {
            Mail::to($email)->send(new SupplyRequestReturnedMail($supplyRequest, $motivo));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar el aviso de devolución del suministro', [
                'supply_request_id' => $supplyRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Cerrar saldo: el motivo va al hilo y el pedido queda Cerrado en la misma acción.
     */
    private function closeBalance(
        ClosePurchaseBalanceRequest $request,
        PurchaseFlowStateService $flowState,
        PurchaseRequestAuditLogService $auditLogService,
        Model $source,
        string $field,
        ?string $previous,
        string $closedValue,
        string $redirect,
        string $message,
    ): RedirectResponse {
        try {
            $flowState->closeBalance($source, $request->user(), (string) $request->validated('motivo'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['motivo' => $exception->getMessage()])->withInput();
        }

        $auditLogService->logModelChange(
            eventType: $source instanceof PurchaseRequest ? 'compras_processing' : 'supply_compras',
            action: 'close_balance',
            model: $source->fresh(),
            before: [$field => $previous],
            after: [$field => $closedValue],
            reason: (string) $request->validated('motivo'),
            metadata: [
                'folio' => $source->folio(),
            ],
        );

        return redirect($redirect)->with('status', $message);
    }
}
