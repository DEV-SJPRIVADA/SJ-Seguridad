<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Enums\PedidoEtapa;
use App\Exports\PurchaseRequestItemsImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ImportPurchaseRequestItemsRequest;
use App\Http\Requests\PurchaseRequests\StorePurchaseRequestCommentRequest;
use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseRequestRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAttachment;
use App\Models\PurchaseRequestComment;
use App\Models\PurchaseRequestItem;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseMyRequestsFilterBag;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use App\Services\PurchaseRequests\PurchaseOrderService;
use App\Services\PurchaseRequests\PurchaseRequestAttachmentService;
use App\Services\PurchaseRequests\PurchaseRequestAuditLogService;
use App\Services\PurchaseRequests\PurchaseRequestExcelExporter;
use App\Services\PurchaseRequests\PurchaseRequestItemsImportService;
use App\Services\PurchaseRequests\PurchaseRequestNotificationService;
use App\Services\PurchaseRequests\PurchaseRequestPdfService;
use App\Services\PurchaseRequests\PurchaseRequestResubmitService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseRequestController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module, Request $request): View
    {
        $filters = PurchaseMyRequestsFilterBag::fromRequest($request);

        $query = PurchaseRequest::query()
            ->with(['user', 'aprobador', 'items'])
            ->where('user_id', auth()->id());

        $filters->apply($query);

        return view('modules.purchase-requests.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequests' => $query->latest()->get(),
            'filters' => $filters->toViewArray(),
            'hasActiveFilters' => $filters->hasActiveFilters(),
            'areas' => config('access.areas', []),
            'etapas' => PedidoEtapa::options(),
        ]);
    }

    public function create(string $module, PurchaseAccessService $accessService): View
    {
        return view('modules.purchase-requests.create', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'directores' => $accessService->approversQuery()->get(),
            'areas' => collect(config('access.areas', [])),
        ]);
    }

    public function importItemsTemplate(
        string $module,
        PurchaseRequestItemsImportTemplateExport $export,
    ): StreamedResponse {
        return $export->download('plantilla_items_solicitud_compra.xlsx');
    }

    public function importItems(
        ImportPurchaseRequestItemsRequest $request,
        string $module,
        PurchaseRequestItemsImportService $importService,
    ): JsonResponse {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return response()->json([
                'message' => 'No se pudo leer el archivo subido.',
            ], 422);
        }

        try {
            $result = $importService->parse($path);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Se cargaron '.count($result['items']).' producto(s) en la tabla.',
            'items' => $result['items'],
            'skipped' => $result['skipped'],
            'warnings' => $result['warnings'],
        ]);
    }

    public function store(
        StorePurchaseRequestRequest $request,
        string $module,
        PurchaseAccessService $accessService,
        PurchaseRequestNotificationService $notifications,
        PurchaseRequestAuditLogService $auditLogService,
        PurchaseRequestAttachmentService $attachmentService,
    ): RedirectResponse {
        $validated = $request->validated();

        $aprobador = $accessService->approversQuery()
            ->whereKey($validated['aprobador_id'])
            ->first();

        if ($aprobador === null) {
            return back()->withErrors([
                'aprobador_id' => 'Seleccione un director valido de la lista.',
            ])->withInput();
        }

        $itemsInput = $request->input('items', []);

        $totalCantidad = collect($itemsInput)->sum(fn ($item) => (int) ($item['cantidad'] ?? 0));

        $purchaseRequest = DB::transaction(function () use ($request, $validated, $aprobador, $itemsInput, $totalCantidad, $module, $attachmentService) {
            $next = ((int) PurchaseRequest::query()->lockForUpdate()->max('numero_solicitud')) + 1;

            $purchaseRequest = PurchaseRequest::create([
                'numero_solicitud' => $next,
                'user_id' => $request->user()->id,
                'area_key' => $validated['area_key'] ?? $module,
                'fecha_solicitud' => $validated['fecha_solicitud'],
                'cantidad' => max(1, $totalCantidad),
                'numero_cotizacion' => filled($validated['numero_cotizacion'] ?? null) ? trim((string) $validated['numero_cotizacion']) : null,
                'comentarios_solicitante' => filled($validated['comentarios_solicitante'] ?? null) ? trim((string) $validated['comentarios_solicitante']) : null,
                'solicitud_para' => $validated['solicitud_para'],
                'urgente' => (bool) $validated['urgente'],
                'aprobador_id' => $aprobador->id,
                'proyecto_nuevo' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['proyecto_nuevo'] ?? false) : null,
                'razon_social' => $validated['solicitud_para'] === 'Cliente' ? ($validated['razon_social'] ?? null) : null,
                'asume_cliente' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['asume_cliente'] ?? false) : null,
                'reinversion' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['reinversion'] ?? false) : null,
                'comodato' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['comodato'] ?? false) : null,
                'activo' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['activo'] ?? false) : null,
                'estado' => PurchaseRequest::ESTADO_PENDIENTE,
            ]);

            foreach ($itemsInput as $index => $itemData) {
                $fotoPath = null;
                if ($request->hasFile("items.{$index}.foto")) {
                    $fotoPath = $request->file("items.{$index}.foto")->store('purchase-request-items', 'public');
                }

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'orden' => $index + 1,
                    'cantidad' => (int) $itemData['cantidad'],
                    'foto_path' => $fotoPath,
                    'descripcion' => $itemData['descripcion'],
                    'referencia' => $itemData['referencia'],
                    'marca' => filled($itemData['marca'] ?? null) ? trim((string) $itemData['marca']) : null,
                    'utilizacion' => $itemData['utilizacion'],
                    'ubicacion' => $itemData['ubicacion'],
                ]);
            }

            $attachmentService->storeMany($purchaseRequest, $attachmentService->filesFromRequest($request));

            return $purchaseRequest;
        });

        $purchaseRequest->loadCount(['items', 'attachments']);

        $auditLogService->logEvent(
            eventType: 'purchase_request',
            action: 'create',
            metadata: [
                'numero_solicitud' => $purchaseRequest->numero_solicitud,
                'area_key' => $purchaseRequest->area_key,
                'items_count' => $purchaseRequest->items_count,
                'attachments_count' => $purchaseRequest->attachments_count,
                'urgente' => $purchaseRequest->urgente,
                'aprobador_id' => $purchaseRequest->aprobador_id,
            ],
            model: $purchaseRequest,
        );

        $notifications->notifyDirectorAssignedAfterResponse($purchaseRequest, $aprobador);

        return redirect()
            ->route('purchase-requests.index', ['module' => $module])
            ->with(
                'status',
                'Solicitud N.º '.$purchaseRequest->folioVisible().' guardada correctamente. El director recibirá un correo para autorizar.',
            );
    }

    public function edit(
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseAccessService $accessService,
    ): View {
        Gate::authorize('resubmit', $purchaseRequest);

        $purchaseRequest->load(['items', 'aprobador', 'attachments', 'comments']);

        return view('modules.purchase-requests.edit', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequest' => $purchaseRequest,
            'directores' => $accessService->approversQuery()->get(),
            'areas' => collect(config('access.areas', [])),
        ]);
    }

    public function update(
        UpdatePurchaseRequestRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseAccessService $accessService,
        PurchaseRequestResubmitService $resubmitService,
        PurchaseRequestNotificationService $notifications,
        PurchaseRequestAuditLogService $auditLogService,
    ): RedirectResponse {
        $validated = $request->validated();

        $previousEstado = $purchaseRequest->estado;

        $aprobador = $accessService->approversQuery()
            ->whereKey($validated['aprobador_id'])
            ->first();

        if ($aprobador === null) {
            return back()->withErrors([
                'aprobador_id' => 'Seleccione un director valido de la lista.',
            ])->withInput();
        }

        $purchaseRequest = $resubmitService->resubmit(
            $purchaseRequest,
            $validated,
            $request,
            $aprobador,
        );

        $auditLogService->logEvent(
            eventType: 'purchase_request',
            action: 'resubmit',
            metadata: [
                'numero_solicitud' => $purchaseRequest->numero_solicitud,
                'items_count' => $purchaseRequest->items()->count(),
                'attachments_count' => $purchaseRequest->attachments()->count(),
                'previous_estado' => $previousEstado,
            ],
            model: $purchaseRequest,
        );

        $notifications->notifyDirectorAssignedAfterResponse($purchaseRequest, $aprobador);

        return redirect()
            ->route('purchase-requests.index', ['module' => $module])
            ->with(
                'status',
                'Solicitud N.º '.$purchaseRequest->folioVisible().' reenviada al director para autorización. El director recibirá un correo para autorizar.',
            );
    }

    public function show(string $module, PurchaseRequest $purchaseRequest): View
    {
        Gate::authorize('view', $purchaseRequest);

        $purchaseRequest->load([
            'user',
            'aprobador',
            'items',
            'procesadoComprasPor',
            'mailLogs',
            'attachments',
            'comments.user',
            'purchaseOrders.items',
            'purchaseOrders.evaluation',
        ]);

        return view('modules.purchase-requests.show', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequest' => $purchaseRequest,
            'orderLines' => $this->orderedByItem(PurchaseOrderService::SOURCE_PURCHASE, $purchaseRequest),
            'orderPdfUrl' => fn (PurchaseOrder $order): string => route('purchase-requests.source-orders.pdf', [
                'module' => $module,
                'purchase_request' => $purchaseRequest->id,
                'purchase_order' => $order->getKey(),
            ]),
        ]);
    }

    /**
     * PDF FO-AD-07 de una OC de esta solicitud, para quien puede ver la solicitud (solicitante, director, Compras).
     */
    public function orderPdf(
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderPdfExporter $exporter,
        PurchaseRequestAuditLogService $auditLogService,
    ): Response {
        Gate::authorize('view', $purchaseRequest);

        abort_unless(
            $purchaseOrder->source_type === PurchaseOrderService::SOURCE_PURCHASE
                && (int) $purchaseOrder->source_id === (int) $purchaseRequest->id,
            404,
        );

        $auditLogService->logEvent(
            eventType: 'export',
            action: 'order_pdf_from_request',
            metadata: ['purchase_order_id' => $purchaseOrder->getKey(), 'numero_oc' => $purchaseOrder->numeroOc],
            model: $purchaseOrder,
        );

        return response($exporter->generate($purchaseOrder), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$exporter->filename($purchaseOrder).'"',
        ]);
    }

    /**
     * Cantidad ordenada y pendiente por línea (solo si hay órdenes), indexada por id de línea.
     *
     * @return array<int, array{ordenada: float, pendiente: float}>
     */
    private function orderedByItem(string $sourceType, PurchaseRequest $source): array
    {
        if ($source->purchaseOrders->isEmpty()) {
            return [];
        }

        $lines = [];

        foreach (app(PurchaseOrderService::class)->sourceLines($sourceType, $source) as $line) {
            $lines[$line['source_item_id']] = [
                'ordenada' => (float) $line['cantidad_ordenada'],
                'pendiente' => (float) $line['cantidad_pendiente'],
            ];
        }

        return $lines;
    }

    public function storeComment(
        StorePurchaseRequestCommentRequest $request,
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseRequestAuditLogService $auditLogService,
    ): RedirectResponse {
        $validated = $request->validated();

        $comment = PurchaseRequestComment::query()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $auditLogService->logEvent(
            eventType: 'purchase_request',
            action: 'comment',
            metadata: [
                'comment_id' => $comment->id,
                'body_length' => mb_strlen($validated['body']),
            ],
            model: $purchaseRequest,
        );

        return redirect()
            ->route('purchase-requests.show', [
                'module' => $module,
                'purchase_request' => $purchaseRequest->id,
                'from' => $request->query('from', $request->input('from')),
            ])
            ->with('status', 'Comentario agregado a la solicitud.')
            ->withFragment('purchase-request-comments');
    }

    public function exportPdf(string $module, PurchaseRequest $purchaseRequest, PurchaseRequestPdfService $pdfService): Response
    {
        Gate::authorize('view', $purchaseRequest);

        return response($pdfService->generate($purchaseRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfService->filename($purchaseRequest).'"',
        ]);
    }

    public function exportExcel(string $module, PurchaseRequest $purchaseRequest, PurchaseRequestExcelExporter $exporter): Response
    {
        Gate::authorize('view', $purchaseRequest);

        $purchaseRequest->load('items');

        return $exporter->toDownloadResponse($purchaseRequest);
    }

    public function downloadAttachment(
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseRequestAttachment $attachment,
    ): StreamedResponse {
        Gate::authorize('view', $purchaseRequest);

        $disk = (string) config('purchase-requests.attachments.disk', 'local');

        abort_unless(Storage::disk($disk)->exists($attachment->stored_path), 404);

        return Storage::disk($disk)->download($attachment->stored_path, $attachment->original_name);
    }
}
