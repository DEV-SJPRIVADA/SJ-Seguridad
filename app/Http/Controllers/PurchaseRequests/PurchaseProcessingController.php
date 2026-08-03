<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ProcessPurchaseComprasRequest;
use App\Http\Requests\PurchaseRequests\ProcessSupplyComprasRequest;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Services\Compras\ComprasQueueFilterBag;
use App\Services\Compras\ComprasQueueService;
use App\Services\PurchaseRequests\PurchaseRequestNotificationService;
use App\Services\Supplies\SupplyPurchasePdfExporter;
use App\Services\Supplies\SupplyPurchaseReportExporter;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseProcessingController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module, Request $request, ComprasQueueService $queueService): View
    {
        $filters = ComprasQueueFilterBag::fromRequest($request);
        $queueItems = $queueService->items($filters);

        return view('modules.purchase-requests.processing.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'queueItems' => $queueItems,
            'filters' => $filters->toViewArray(),
            'areas' => config('access.areas', []),
            'estadosCompras' => PurchaseRequest::estadosComprasLabels(),
            'queueCount' => $queueItems->count(),
        ]);
    }

    public function editPurchase(string $module, PurchaseRequest $purchaseRequest): View
    {
        $this->authorize('process', $purchaseRequest);

        $purchaseRequest->load(['user', 'aprobador', 'items']);

        return view('modules.purchase-requests.processing.process-purchase', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequest' => $purchaseRequest,
            'estadosCompras' => PurchaseRequest::estadosComprasLabels(),
        ]);
    }

    public function updatePurchase(
        ProcessPurchaseComprasRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseRequestNotificationService $notifications,
    ): RedirectResponse {
        $this->authorize('process', $purchaseRequest);

        $estadoCompras = $request->validated('estado_compras');

        $purchaseRequest->update([
            'estado_compras' => $estadoCompras,
            'comentarios_compras' => $request->validated('comentarios_compras'),
            'procesado_compras_at' => $estadoCompras === PurchaseRequest::COMPRAS_COMPLETADO ? now() : $purchaseRequest->procesado_compras_at,
            'procesado_compras_por' => auth()->id(),
        ]);

        $notifications->notifyRequesterProcessed($purchaseRequest->fresh());

        return redirect()
            ->route('purchase-requests.processing.index', ['module' => $module, 'estado_compras' => $request->input('redirect_estado_compras')])
            ->with('status', 'Solicitud N.º '.$purchaseRequest->folio().' actualizada.');
    }

    public function editSupply(string $module, SupplyRequest $supplyRequest): View|RedirectResponse
    {
        abort_unless(in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras'], true), 403);

        if ($supplyRequest->status === 'aprobada_calidad') {
            $supplyRequest->update([
                'status' => 'en_compras',
                'purchasing_manager_id' => auth()->id(),
            ]);
        }

        $supplyRequest->load(['user', 'items.product']);

        return view('modules.purchase-requests.processing.process-supply', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'supplyRequest' => $supplyRequest,
        ]);
    }

    public function updateSupply(ProcessSupplyComprasRequest $request, string $module, SupplyRequest $supplyRequest): RedirectResponse
    {
        abort_unless(in_array($supplyRequest->status, ['en_compras', 'aprobada_calidad'], true), 403);

        DB::transaction(function () use ($request, $supplyRequest): void {
            foreach ($request->input('items', []) as $itemId => $data) {
                $supplyRequest->items()->whereKey($itemId)->update([
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'purchasing_observations' => $request->input("purchasing_observations.{$itemId}"),
                ]);
            }

            if ($request->input('action') === 'complete') {
                $totalCost = $supplyRequest->items()
                    ->where('approved_quantity', '>', 0)
                    ->get()
                    ->sum(fn ($item) => ((float) ($item->unit_cost ?? 0)) * (int) ($item->approved_quantity ?? 0));

                $supplyRequest->update([
                    'status' => 'completada',
                    'purchasing_manager_id' => auth()->id(),
                    'total_cost' => $totalCost,
                ]);
            } else {
                $supplyRequest->update([
                    'status' => 'en_compras',
                    'purchasing_manager_id' => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('purchase-requests.processing.index', ['module' => $module])
            ->with('status', 'Suministro #'.$supplyRequest->id.' actualizado.');
    }

    public function exportSupplyPdf(string $module, SupplyRequest $supplyRequest, SupplyPurchasePdfExporter $exporter): Response
    {
        abort_unless(in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras', 'completada'], true), 403);

        return response($exporter->generate($supplyRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$exporter->filename($supplyRequest).'"',
        ]);
    }

    public function exportSupplyExcel(string $module, SupplyRequest $supplyRequest, SupplyPurchaseReportExporter $exporter): Response
    {
        abort_unless(in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras', 'completada'], true), 403);

        $rows = $exporter->buildMergedRowsForRequest($supplyRequest);

        if (! $supplyRequest->exported_at) {
            $supplyRequest->update(['exported_at' => now()]);
        }

        return $exporter->toDownloadResponseForRequest($supplyRequest, $rows);
    }
}
