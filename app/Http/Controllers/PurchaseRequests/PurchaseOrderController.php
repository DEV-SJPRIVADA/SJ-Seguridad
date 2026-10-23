<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Exports\PurchaseOrderLinesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\PurchaseTaxRate;
use App\Models\SupplyRequest;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseFlowStateService;
use App\Services\PurchaseRequests\PurchaseOrderDatatableService;
use App\Services\PurchaseRequests\PurchaseOrderExcelExporter;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use App\Services\PurchaseRequests\PurchaseOrderService;
use App\Services\PurchaseRequests\PurchaseRequestAuditLogService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseOrderController extends Controller
{
    use HasPurchaseTabs;

    public function createFromPurchase(
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseOrderService $service,
    ): View {
        Gate::authorize('process', $purchaseRequest);
        abort_unless($purchaseRequest->puedeEmitirOrden(), 403);

        $purchaseRequest->load(['items', 'user']);

        return $this->createView(
            module: $module,
            sourceType: 'purchase',
            source: $purchaseRequest,
            sourceLabel: 'Solicitud de compra N.º '.$purchaseRequest->folioVisible(),
            lines: $service->buildLinesFromPurchase($purchaseRequest),
            storeUrl: route('purchase-requests.orders.purchase.store', [
                'module' => $module,
                'purchase_request' => $purchaseRequest->getKey(),
            ]),
            backUrl: route('purchase-requests.processing.purchase', [
                'module' => $module,
                'purchase_request' => $purchaseRequest->getKey(),
            ]),
            defaultCity: '',
        );
    }

    public function createFromSupply(
        string $module,
        SupplyRequest $supplyRequest,
        PurchaseOrderService $service,
    ): View {
        $this->assertSupplyIsProcessable($supplyRequest);

        $supplyRequest->load(['items.product', 'site']);

        return $this->createView(
            module: $module,
            sourceType: 'supply',
            source: $supplyRequest,
            sourceLabel: 'Solicitud de suministro N.º '.$supplyRequest->folioVisible(),
            lines: $service->buildLinesFromSupply($supplyRequest),
            storeUrl: route('purchase-requests.orders.supply.store', [
                'module' => $module,
                'supply_request' => $supplyRequest->getKey(),
            ]),
            backUrl: route('purchase-requests.processing.supply', [
                'module' => $module,
                'supply_request' => $supplyRequest->getKey(),
            ]),
            defaultCity: (string) ($supplyRequest->site?->city ?? $supplyRequest->site_city ?? ''),
        );
    }

    public function storeFromPurchase(
        StorePurchaseOrderRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseOrderService $service,
        PurchaseRequestAuditLogService $audit,
        PurchaseFlowStateService $flowState,
    ): RedirectResponse {
        Gate::authorize('process', $purchaseRequest);

        try {
            $order = $service->createFromPurchase($purchaseRequest, $request->validated(), $request->user());
            $flowState->sync($purchaseRequest->fresh(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['items' => $exception->getMessage()]);
        }

        $this->auditEmitted($audit, $order);

        return redirect()
            ->route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $order->getKey()])
            ->with('status', 'Orden de compra N.º '.$order->numeroOc.' emitida.');
    }

    public function storeFromSupply(
        StorePurchaseOrderRequest $request,
        string $module,
        SupplyRequest $supplyRequest,
        PurchaseOrderService $service,
        PurchaseRequestAuditLogService $audit,
        PurchaseFlowStateService $flowState,
    ): RedirectResponse {
        $this->assertSupplyIsProcessable($supplyRequest);

        try {
            $order = $service->createFromSupply($supplyRequest, $request->validated(), $request->user());
            $flowState->sync($supplyRequest->fresh(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['items' => $exception->getMessage()]);
        }

        $this->auditEmitted($audit, $order);

        return redirect()
            ->route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $order->getKey()])
            ->with('status', 'Orden de compra N.º '.$order->numeroOc.' emitida.');
    }

    public function index(string $module, Request $request, PurchaseOrderDatatableService $datatable): View
    {
        $filters = $datatable->filtersFromRequest($request);

        return view('modules.purchase-requests.orders.index', [
            'module' => $module,
            'filters' => $filters,
            'estados' => $datatable->estadoOptions(),
            'tipos' => $datatable->tipoOptions(),
            'proveedores' => $datatable->providerOptions(),
            'indexUrl' => route('purchase-requests.orders.index', ['module' => $module]),
            'datatableUrl' => route('purchase-requests.orders.datatable', array_merge(
                ['module' => $module],
                $datatable->filtersToQuery($filters),
            )),
            'exportUrl' => route('purchase-requests.orders.export', array_merge(
                ['module' => $module],
                $datatable->filtersToQuery($filters),
            )),
        ]);
    }

    public function datatable(string $module, Request $request, PurchaseOrderDatatableService $datatable): JsonResponse
    {
        return $datatable->respond($request, $datatable->filtersFromRequest($request));
    }

    /**
     * Excel con todas las líneas de producto de las órdenes que cumplen los filtros y la búsqueda (`q`) del listado.
     */
    public function export(
        string $module,
        Request $request,
        PurchaseOrderDatatableService $datatable,
        PurchaseRequestAuditLogService $audit,
    ): StreamedResponse {
        $filters = $datatable->filtersFromRequest($request);
        $search = trim((string) $request->query('q', ''));

        $query = $datatable->filteredQuery($filters);
        $datatable->applySearchTerm($query, $search);
        $orders = $query->orderByDesc('fecha')->orderByDesc('id')->get();

        $audit->logEvent(
            eventType: 'export',
            action: 'orders_lines_excel',
            metadata: ['filters' => $filters, 'q' => $search, 'orders_count' => $orders->count()],
        );

        return (new PurchaseOrderLinesExport($orders))->download();
    }

    public function show(string $module, PurchaseOrder $purchaseOrder, PurchaseAccessService $access): View
    {
        $purchaseOrder->load(['items', 'source', 'provider', 'evaluation', 'anulacionAprobador', 'anulacionSolicitadaPor']);
        $canProcess = auth()->user() !== null && $access->canEmitOrder(auth()->user());

        return view('modules.purchase-requests.orders.show', [
            'canRequestCancellation' => $canProcess && $purchaseOrder->puedeSolicitarAnulacion(),
            'canWithdrawCancellation' => $canProcess && $purchaseOrder->tieneAnulacionPendiente(),
            'approverOptions' => $access->approversQuery()->get(['id', 'name'])
                ->map(fn ($user): array => ['value' => (string) $user->id, 'label' => $user->name])
                ->all(),
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'order' => $purchaseOrder,
            'pdfUrl' => route('purchase-requests.orders.pdf', ['module' => $module, 'purchase_order' => $purchaseOrder->getKey()]),
            'excelUrl' => route('purchase-requests.orders.excel', ['module' => $module, 'purchase_order' => $purchaseOrder->getKey()]),
            'backUrl' => $this->backUrlFor($module, $purchaseOrder),
            'sourceUrl' => $this->sourceUrlFor($module, $purchaseOrder),
            'siblings' => $this->siblingOrders($purchaseOrder),
            'sourceLines' => $this->sourceLinesFor($purchaseOrder),
            'evaluationUrl' => $this->evaluationUrlFor($module, $purchaseOrder),
        ]);
    }

    public function pdf(
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderPdfExporter $exporter,
        PurchaseRequestAuditLogService $audit,
    ): Response {
        $purchaseOrder->loadMissing(['items', 'source']);

        $audit->logEvent(
            eventType: 'export',
            action: 'order_pdf',
            metadata: ['purchase_order_id' => $purchaseOrder->getKey(), 'numero_oc' => $purchaseOrder->numeroOc],
            model: $purchaseOrder,
        );

        return response($exporter->generate($purchaseOrder), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$exporter->filename($purchaseOrder).'"',
        ]);
    }

    public function excel(
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderExcelExporter $exporter,
        PurchaseRequestAuditLogService $audit,
    ): StreamedResponse {
        $purchaseOrder->loadMissing(['items', 'source']);

        $audit->logEvent(
            eventType: 'export',
            action: 'order_excel',
            metadata: ['purchase_order_id' => $purchaseOrder->getKey(), 'numero_oc' => $purchaseOrder->numeroOc],
            model: $purchaseOrder,
        );

        return $exporter->toDownloadResponse($purchaseOrder);
    }

    /**
     * @param  list<array{orden: int, cantidad: float, articulo: string, descripcion: string, valor_unidad: float, iva: float}>  $lines
     */
    private function createView(
        string $module,
        string $sourceType,
        PurchaseRequest|SupplyRequest $source,
        string $sourceLabel,
        array $lines,
        string $storeUrl,
        string $backUrl,
        string $defaultCity,
    ): View {
        return view('modules.purchase-requests.orders.create', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'sourceType' => $sourceType,
            'source' => $source,
            'sourceLabel' => $sourceLabel,
            'lines' => $lines,
            'providerOptions' => $this->providerOptions(),
            'taxRateOptions' => $this->taxRateOptions(),
            'defaultTaxRate' => $this->defaultTaxRate(),
            'storeUrl' => $storeUrl,
            'backUrl' => $backUrl,
            'defaultCity' => $defaultCity,
            'issuedOrders' => $this->issuedOrdersFor($sourceType, (int) $source->getKey()),
        ]);
    }

    /**
     * OC ya emitidas desde el mismo origen (reparto entre proveedores).
     *
     * @return Collection<int, PurchaseOrder>
     */
    private function issuedOrdersFor(string $sourceType, int $sourceId): Collection
    {
        $morphType = $sourceType === 'purchase'
            ? PurchaseOrderService::SOURCE_PURCHASE
            : PurchaseOrderService::SOURCE_SUPPLY;

        return PurchaseOrder::query()
            ->where('source_type', $morphType)
            ->where('source_id', $sourceId)
            ->orderBy('id')
            ->get(['id', 'provider_nombre', 'fecha', 'total', 'estado']);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function providerOptions(): array
    {
        return PurchaseProvider::query()
            ->where('estado', PurchaseProvider::ESTADO_ACTIVO)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'identificacion'])
            ->map(fn (PurchaseProvider $provider): array => [
                'value' => (string) $provider->getKey(),
                'label' => $provider->nombre.' — '.$provider->identificacion,
            ])
            ->all();
    }

    /**
     * Tasas de IVA activas para el selector de cada línea (valor = porcentaje).
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function taxRateOptions(): array
    {
        return PurchaseTaxRate::query()
            ->activas()
            ->get()
            ->map(fn (PurchaseTaxRate $rate): array => [
                'value' => rtrim(rtrim(number_format((float) $rate->porcentaje, 2, '.', ''), '0'), '.'),
                'label' => $rate->label(),
            ])
            ->all();
    }

    /**
     * Tasa por defecto: 19% si está activa; si no, la primera activa; si no hay catálogo, vacío.
     */
    private function defaultTaxRate(): string
    {
        $options = collect($this->taxRateOptions());
        $default = $options->firstWhere('value', rtrim(rtrim(number_format(PurchaseTaxRate::DEFAULT_PORCENTAJE, 2, '.', ''), '0'), '.'));

        return (string) (($default ?? $options->first())['value'] ?? '');
    }

    private function assertSupplyIsProcessable(SupplyRequest $supplyRequest): void
    {
        abort_unless(
            $supplyRequest->puedeEmitirOrden(),
            403,
        );
    }

    /**
     * Otras OC emitidas desde el mismo origen (reparto entre proveedores).
     *
     * @return Collection<int, PurchaseOrder>
     */
    private function siblingOrders(PurchaseOrder $order): Collection
    {
        return PurchaseOrder::query()
            ->where('source_type', $order->source_type)
            ->where('source_id', $order->source_id)
            ->whereKeyNot($order->getKey())
            ->orderBy('id')
            ->get(['id', 'provider_nombre', 'fecha', 'total', 'estado']);
    }

    /**
     * Solicitado / ordenado / pendiente por línea del origen.
     *
     * @return array<int, array{solicitada: float, ordenada: float, pendiente: float}>
     */
    private function sourceLinesFor(PurchaseOrder $order): array
    {
        $source = $order->source;

        if (! $source instanceof PurchaseRequest && ! $source instanceof SupplyRequest) {
            return [];
        }

        $service = app(PurchaseOrderService::class);
        $sourceType = $source instanceof PurchaseRequest
            ? PurchaseOrderService::SOURCE_PURCHASE
            : PurchaseOrderService::SOURCE_SUPPLY;

        $lines = [];

        foreach ($service->sourceLines($sourceType, $source) as $line) {
            $lines[$line['source_item_id']] = [
                'solicitada' => (float) $line['cantidad_solicitada'],
                'ordenada' => (float) $line['cantidad_ordenada'],
                'pendiente' => (float) $line['cantidad_pendiente'],
            ];
        }

        return $lines;
    }

    private function auditEmitted(PurchaseRequestAuditLogService $audit, PurchaseOrder $order): void
    {
        $audit->logModelChange(
            eventType: 'purchase_order',
            action: 'created',
            model: $order,
            before: null,
            after: [
                'source_type' => $order->source_type,
                'source_id' => $order->source_id,
                'provider_id' => $order->provider_id,
                'estado' => $order->estado,
                'total' => $order->total,
            ],
            metadata: ['numero_oc' => $order->numeroOc, 'total' => $order->total],
        );
    }

    private function backUrlFor(string $module, PurchaseOrder $order): string
    {
        return match ($order->source_type) {
            'purchase_request', PurchaseRequest::class => route('purchase-requests.processing.purchase', [
                'module' => $module,
                'purchase_request' => $order->source_id,
            ]),
            'supply_request', SupplyRequest::class => route('purchase-requests.processing.supply', [
                'module' => $module,
                'supply_request' => $order->source_id,
            ]),
            default => route('purchase-requests.processing.index', ['module' => $module]),
        };
    }

    /**
     * Enlace a la evaluación. Solo quien edita proveedores (el permiso de procesamiento no alcanza).
     */
    private function evaluationUrlFor(string $module, PurchaseOrder $order): ?string
    {
        $user = auth()->user();

        if ($order->estado !== 'emitida' || $user === null || ! $user->canAccessPurchaseTab($module, 'providers.edit')) {
            return null;
        }

        return route('purchase-requests.orders.evaluation.edit', [
            'module' => $module,
            'purchase_order' => $order->getKey(),
        ]);
    }

    private function sourceUrlFor(string $module, PurchaseOrder $order): ?string
    {
        return match ($order->source_type) {
            'purchase_request', PurchaseRequest::class => route('purchase-requests.show', [
                'module' => $module,
                'purchase_request' => $order->source_id,
                'from' => 'processing',
            ]),
            'supply_request', SupplyRequest::class => route('supplies.show', [
                'module' => $order->source?->area_key ?? 'compras',
                'supply_request' => $order->source_id,
            ]),
            default => null,
        };
    }
}
