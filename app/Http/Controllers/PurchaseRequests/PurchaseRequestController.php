<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseRequestRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAttachment;
use App\Models\PurchaseRequestItem;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseRequestAttachmentService;
use App\Services\PurchaseRequests\PurchaseRequestAuditLogService;
use App\Services\PurchaseRequests\PurchaseRequestExcelExporter;
use App\Services\PurchaseRequests\PurchaseRequestNotificationService;
use App\Services\PurchaseRequests\PurchaseRequestPdfService;
use App\Services\PurchaseRequests\PurchaseRequestResubmitService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseRequestController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module): View
    {
        $purchaseRequests = PurchaseRequest::query()
            ->with(['user', 'aprobador', 'items'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('modules.purchase-requests.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequests' => $purchaseRequests,
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
                'solicitud_para' => $validated['solicitud_para'],
                'urgente' => (bool) $validated['urgente'],
                'aprobador_id' => $aprobador->id,
                'proyecto_nuevo' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['proyecto_nuevo'] ?? false) : null,
                'razon_social' => $validated['solicitud_para'] === 'Cliente' ? ($validated['razon_social'] ?? null) : null,
                'asume_cliente' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['asume_cliente'] ?? false) : null,
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
                'Solicitud N.º '.$purchaseRequest->folio().' guardada correctamente. El director recibira un correo para autorizar.',
            );
    }

    public function edit(
        string $module,
        PurchaseRequest $purchaseRequest,
        PurchaseAccessService $accessService,
    ): View {
        Gate::authorize('resubmit', $purchaseRequest);

        $purchaseRequest->load(['items', 'aprobador', 'attachments']);

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
                'Solicitud N.º '.$purchaseRequest->folio().' reenviada al director para autorizacion. El director recibira un correo para autorizar.',
            );
    }

    public function show(string $module, PurchaseRequest $purchaseRequest): View
    {
        Gate::authorize('view', $purchaseRequest);

        $purchaseRequest->load(['user', 'aprobador', 'items', 'procesadoComprasPor', 'mailLogs', 'attachments']);

        return view('modules.purchase-requests.show', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequest' => $purchaseRequest,
        ]);
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
