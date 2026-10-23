<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\RequestPurchaseOrderApprovalRequest;
use App\Http\Requests\PurchaseRequests\ResolvePurchaseOrderApprovalRequest;
use App\Models\PurchaseOrder;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseOrderApprovalService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Aprobación opcional de OC: Compras la pide (eligiendo director o administrador) o la retira;
 * el aprobador decide en la plataforma o por el enlace firmado del correo.
 */
class PurchaseOrderApprovalController extends Controller
{
    use HasPurchaseTabs;

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
            ->with('status', 'Aprobación de la orden N.º '.$purchaseOrder->numeroOc.' solicitada.');
    }

    public function withdraw(
        Request $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
        PurchaseAccessService $access,
    ): RedirectResponse {
        abort_unless($access->canEmitOrder($request->user()), 403);

        try {
            $service->withdraw($purchaseOrder, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['aprobacion_aprobador_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $purchaseOrder->getKey()])
            ->with('status', 'Solicitud de aprobación de la orden N.º '.$purchaseOrder->numeroOc.' retirada.');
    }

    /**
     * Revisión en la plataforma (Autorizaciones compras → Órdenes de Compra).
     */
    public function show(Request $request, string $module, PurchaseOrder $purchaseOrder): View
    {
        abort_unless((int) $purchaseOrder->aprobacion_aprobador_id === (int) $request->user()->id, 403);

        return view('modules.purchase-requests.approval.order-approval', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'order' => $this->loadOrder($purchaseOrder),
            'decideUrl' => route('purchase-requests.approval.order-approval.update', [
                'module' => $module,
                'purchase_order' => $purchaseOrder->getKey(),
            ]),
            'backUrl' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'ordenes']),
        ]);
    }

    public function update(
        ResolvePurchaseOrderApprovalRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderApprovalService $service,
    ): RedirectResponse {
        $approve = $request->validated('decision') === 'aprobar';

        try {
            $service->resolve($purchaseOrder, (int) $request->user()->id, $approve, $request->validated('comentario_aprobacion'), 'web');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['comentario_aprobacion' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'ordenes'])
            ->with('status', 'Orden N.º '.$purchaseOrder->numeroOc.' '.($approve ? 'aprobada' : 'rechazada y anulada').'.');
    }

    /**
     * Enlace firmado del correo (sin login).
     */
    public function emailShow(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderApprovalService $service): View
    {
        $this->forceUrlsFromRequest($request);
        $pending = $purchaseOrder->tieneAprobacionPendiente();

        // Si ya se resolvió o Compras la retiró, el enlace firmado solo informa.
        if ($pending) {
            $this->assertEmailApprover($request, $purchaseOrder);
        }

        return view('modules.purchase-requests.order-approval-email', [
            'order' => $this->loadOrder($purchaseOrder),
            'alreadyResolved' => ! $pending,
            'decideUrl' => $pending ? $service->emailUpdateUrl($purchaseOrder) : null,
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

        return view('modules.purchase-requests.order-approval-email', [
            'order' => $this->loadOrder($purchaseOrder->refresh()),
            'alreadyResolved' => true,
            'decideUrl' => null,
            'result' => $approve ? 'aprobada' : 'rechazada (la orden quedó anulada)',
        ]);
    }

    private function loadOrder(PurchaseOrder $order): PurchaseOrder
    {
        return $order->load(['items', 'source', 'aprobacionAprobador', 'aprobacionSolicitadaPor']);
    }

    private function assertEmailApprover(Request $request, PurchaseOrder $order): int
    {
        $approverId = (int) $request->query('director', $request->input('director'));
        abort_unless($approverId > 0 && (int) $order->aprobacion_aprobador_id === $approverId, 403);

        return $approverId;
    }

    /** Los enlaces del formulario usan la misma URL con la que el aprobador abrió el correo. */
    private function forceUrlsFromRequest(Request $request): void
    {
        URL::forceRootUrl($request->root());
        URL::forceScheme($request->getScheme());
    }
}
