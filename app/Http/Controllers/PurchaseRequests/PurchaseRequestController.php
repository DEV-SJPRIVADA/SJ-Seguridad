<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseRequestExcelExporter;
use App\Services\PurchaseRequests\PurchaseRequestNotificationService;
use App\Services\PurchaseRequests\PurchaseRequestPdfService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    use HasPurchaseTabs;

    public function index(string $module): View
    {
        $purchaseRequests = PurchaseRequest::query()
            ->with(['aprobador', 'items'])
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
        $archivoPedido = $request->hasFile('archivo_pedido')
            ? $request->file('archivo_pedido')->store('purchase-requests', 'public')
            : null;

        $totalCantidad = collect($itemsInput)->sum(fn ($item) => (int) ($item['cantidad'] ?? 0));
        $lineCount = count($itemsInput);

        $purchaseRequest = DB::transaction(function () use ($request, $validated, $archivoPedido, $aprobador, $itemsInput, $totalCantidad, $lineCount, $module) {
            $next = ((int) PurchaseRequest::query()->lockForUpdate()->max('numero_solicitud')) + 1;

            $purchaseRequest = PurchaseRequest::create([
                'numero_solicitud' => $next,
                'user_id' => $request->user()->id,
                'area_key' => $validated['area_key'] ?? $module,
                'fecha_solicitud' => $validated['fecha_solicitud'],
                'descripcion' => 'Solicitud con '.$lineCount.' articulo(s) — total '.$totalCantidad.' unidad(es).',
                'cantidad' => max(1, $totalCantidad),
                'justificacion' => 'Detalle en lineas de la solicitud.',
                'archivo_pedido_path' => $archivoPedido,
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

            return $purchaseRequest;
        });

        $correoEnviado = $notifications->notifyDirectorAssigned($purchaseRequest, $aprobador);

        $redirect = redirect()->route('purchase-requests.create', ['module' => $module])
            ->with('status', 'Solicitud N.º '.$purchaseRequest->folio().' guardada correctamente.');

        if (! $correoEnviado) {
            $redirect->with('warning', 'No se pudo enviar el correo a '.$aprobador->email.'.');
        }

        return $redirect;
    }

    public function show(string $module, PurchaseRequest $purchaseRequest): View
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest->load(['user', 'aprobador', 'items', 'procesadoComprasPor', 'mailLogs']);

        return view('modules.purchase-requests.show', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'purchaseRequest' => $purchaseRequest,
        ]);
    }

    public function exportPdf(string $module, PurchaseRequest $purchaseRequest, PurchaseRequestPdfService $pdfService): Response
    {
        $this->authorize('view', $purchaseRequest);

        return response($pdfService->generate($purchaseRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfService->filename($purchaseRequest).'"',
        ]);
    }

    public function exportExcel(string $module, PurchaseRequest $purchaseRequest, PurchaseRequestExcelExporter $exporter): Response
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest->load('items');

        return $exporter->toDownloadResponse($purchaseRequest);
    }
}
