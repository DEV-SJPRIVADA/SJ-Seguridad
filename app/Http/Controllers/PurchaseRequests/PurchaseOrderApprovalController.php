<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\RequestPurchaseOrderApprovalRequest;
use App\Http\Requests\PurchaseRequests\ResolvePurchaseOrderApprovalRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseRequests\PurchaseOrderApprovalService;
use App\Services\PurchaseRequests\PurchaseQuotationFileService;
use App\Traits\HasPurchaseTabs;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Autorización de OC en dos pasos: quien autorizó la solicitud y luego el Director Admon.
 * El aprobador decide en la plataforma o por el enlace firmado del correo.
 */
class PurchaseOrderApprovalController extends Controller
{
    use HasPurchaseTabs;
    use HasSupplyTabs;

    public function request(
        RequestPurchaseOrderApprovalRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
    ): RedirectResponse {
        try {
            $service->request(
                $purchaseOrder,
                $request->user(),
                (int) $request->validated('aprobacion_aprobador_id'),
                $request->validated('aprobacion_nota'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['aprobacion_aprobador_id' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $purchaseOrder->getKey()])
            ->with('status', 'Orden N.º '.$purchaseOrder->numeroOc.' enviada a autorización (área y Director Admon.).');
    }

    /**
     * Revisión en Autorizaciones compras → Órdenes de Compra.
     */
    public function show(Request $request, string $module, PurchaseOrder $purchaseOrder): View
    {
        $this->assertAssignedApprover($request, $purchaseOrder);

        return $this->reviewView(
            $purchaseOrder,
            $this->getPurchaseSubTabs($module),
            route('purchase-requests.approval.order-approval.update', [
                'module' => $module,
                'purchase_order' => $purchaseOrder->getKey(),
            ]),
            route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'ordenes']),
            'Volver a Órdenes de Compra',
        );
    }

    /**
     * Revisión desde Calidad → Por aprobar (paso 1 de una OC de suministro).
     */
    public function showFromQuality(Request $request, string $module, PurchaseOrder $purchaseOrder): View
    {
        $this->assertQualityAreaApprover($request, $purchaseOrder);

        return $this->reviewView(
            $purchaseOrder,
            $this->getSupplySubTabs($module),
            route('supplies.approval.order-approval.update', [
                'module' => $module,
                'purchase_order' => $purchaseOrder->getKey(),
            ]),
            route('supplies.approval.index', ['module' => $module]),
            'Volver a Por aprobar',
        );
    }

    public function update(
        ResolvePurchaseOrderApprovalRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
    ): RedirectResponse {
        return $this->resolveAndRedirect(
            $request,
            $purchaseOrder,
            $service,
            route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'ordenes']),
        );
    }

    public function updateFromQuality(
        ResolvePurchaseOrderApprovalRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
    ): RedirectResponse {
        $this->assertQualityAreaApprover($request, $purchaseOrder);

        return $this->resolveAndRedirect(
            $request,
            $purchaseOrder,
            $service,
            route('supplies.approval.index', ['module' => $module]),
        );
    }

    /**
     * Enlace firmado del correo (sin login). El `director` identifica a qué paso corresponde.
     */
    public function emailShow(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderApprovalService $service): View
    {
        $this->forceUrlsFromRequest($request);
        $approverId = $this->assertEmailApprover($request, $purchaseOrder);
        $pending = $purchaseOrder->tieneAprobacionPendiente();
        $canDecide = $pending && $purchaseOrder->puedeDecidirAprobacion($approverId);
        $waiting = $pending && $purchaseOrder->esperaPasoArea($approverId);

        return view('modules.purchase-requests.order-approval-email', [
            'order' => $this->loadOrder($purchaseOrder),
            'alreadyResolved' => ! $pending,
            'waitingForArea' => $waiting,
            'decideUrl' => $canDecide ? $service->emailUpdateUrl($purchaseOrder, $approverId) : null,
            'result' => null,
        ]);
    }

    public function emailUpdate(
        ResolvePurchaseOrderApprovalRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
    ): View|RedirectResponse {
        $this->forceUrlsFromRequest($request);
        $approverId = $this->assertEmailApprover($request, $purchaseOrder);
        $approve = $request->validated('decision') === 'aprobar';

        if ($purchaseOrder->tieneAprobacionPendiente()) {
            try {
                $service->resolve($purchaseOrder, $approverId, $approve, $request->validated('comentario_aprobacion'), 'email');
            } catch (InvalidArgumentException $exception) {
                return back()->withErrors(['comentario_aprobacion' => $exception->getMessage()])->withInput();
            }
        }

        $order = $this->loadOrder($purchaseOrder->refresh());
        $stillPending = $order->tieneAprobacionPendiente();

        return view('modules.purchase-requests.order-approval-email', [
            'order' => $order,
            'alreadyResolved' => ! $stillPending,
            'waitingForArea' => false,
            'decideUrl' => null,
            'result' => $stillPending
                ? 'con visto bueno del área; falta la autorización del Director Admon.'
                : ($approve ? 'aprobada' : 'rechazada (la orden quedó anulada)'),
        ]);
    }

    /**
     * Vista previa del archivo de la cotización aprobada vinculada a la OC.
     */
    public function quotationFile(Request $request, string $module, PurchaseOrder $purchaseOrder, PurchaseQuotationFileService $files): BinaryFileResponse
    {
        $this->assertAssignedApprover($request, $purchaseOrder);

        $quotation = $purchaseOrder->purchaseQuotation;

        abort_if($quotation === null, 404);

        return $files->response($quotation);
    }

    private function reviewView(PurchaseOrder $purchaseOrder, mixed $subTabs, string $decideUrl, string $backUrl, string $backTitle): View
    {
        $order = $this->loadOrder($purchaseOrder);
        $userId = (int) auth()->id();

        $fromQuality = request()->routeIs('supplies.approval.order-approval.*');

        return view('modules.purchase-requests.approval.order-approval', [
            'module' => request()->route('module'),
            'subTabs' => $subTabs,
            'navPartial' => $fromQuality ? 'modules.supplies.partials.subnav' : 'modules.purchase-requests.partials.subnav',
            'order' => $order,
            'canDecide' => $order->puedeDecidirAprobacion($userId),
            'waitingForArea' => $order->esperaPasoArea($userId),
            'alreadyGaveAreaOk' => $order->esAprobadorDeSolicitud($userId) && $order->pasoAreaAprobado() && $order->tieneAprobacionPendiente() && ! $order->esAprobadorAdmon($userId),
            'decideUrl' => $decideUrl,
            'backUrl' => $backUrl,
            'backTitle' => $backTitle,
        ]);
    }

    private function resolveAndRedirect(
        ResolvePurchaseOrderApprovalRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
        string $redirectUrl,
    ): RedirectResponse {
        $approve = $request->validated('decision') === 'aprobar';

        try {
            $service->resolve($purchaseOrder, (int) $request->user()->id, $approve, $request->validated('comentario_aprobacion'), 'web');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['comentario_aprobacion' => $exception->getMessage()])->withInput();
        }

        $order = $purchaseOrder->fresh();
        $status = $order->tieneAprobacionPendiente()
            ? 'Orden N.º '.$order->numeroOc.' con visto bueno del área. Falta la autorización del Director Admon.'
            : 'Orden N.º '.$order->numeroOc.' '.($approve ? 'aprobada' : 'rechazada y anulada').'.';

        return redirect()->to($redirectUrl)->with('status', $status);
    }

    private function loadOrder(PurchaseOrder $order): PurchaseOrder
    {
        return $order->load([
            'items', 'source', 'aprobacionAprobador', 'aprobacionAreaAprobador',
            'aprobacionSolicitadaPor', 'purchaseQuotation.aprobador',
        ]);
    }

    private function assertAssignedApprover(Request $request, PurchaseOrder $order): void
    {
        abort_unless($order->esAprobadorAsignado((int) $request->user()->id), 403);
    }

    private function assertQualityAreaApprover(Request $request, PurchaseOrder $order): void
    {
        $userId = (int) $request->user()->id;

        abort_unless($order->esAprobadorDeSolicitud($userId), 403);
    }

    private function assertEmailApprover(Request $request, PurchaseOrder $order): int
    {
        $approverId = (int) $request->query('director', $request->input('director'));
        abort_unless($approverId > 0 && $order->esAprobadorAsignado($approverId), 403);

        return $approverId;
    }

    /** Los enlaces del formulario usan la misma URL con la que el aprobador abrió el correo. */
    private function forceUrlsFromRequest(Request $request): void
    {
        URL::forceRootUrl($request->root());
        URL::forceScheme($request->getScheme());
    }
}
