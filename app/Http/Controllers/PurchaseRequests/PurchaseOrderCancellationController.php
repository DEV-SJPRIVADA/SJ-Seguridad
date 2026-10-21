<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\RequestPurchaseOrderCancellationRequest;
use App\Http\Requests\PurchaseRequests\ResolvePurchaseOrderCancellationRequest;
use App\Models\PurchaseOrder;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseOrderCancellationService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Anulación de OC: Compras solicita (eligiendo director) o retira; el director aprueba o rechaza
 * en la plataforma o por el enlace firmado del correo.
 */
class PurchaseOrderCancellationController extends Controller
{
    use HasPurchaseTabs;

    public function request(
        RequestPurchaseOrderCancellationRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderCancellationService $service,
    ): RedirectResponse {
        try {
            $service->request(
                $purchaseOrder,
                $request->user(),
                (int) $request->validated('anulacion_aprobador_id'),
                (string) $request->validated('motivo_anulacion'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['motivo_anulacion' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $purchaseOrder->getKey()])
            ->with('status', 'Anulación de la orden N.º '.$purchaseOrder->numeroOc.' enviada al director para aprobación.');
    }

    public function withdraw(
        Request $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderCancellationService $service,
        PurchaseAccessService $access,
    ): RedirectResponse {
        abort_unless($access->canEmitOrder($request->user()), 403);

        try {
            $service->withdraw($purchaseOrder, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['motivo_anulacion' => $exception->getMessage()]);
        }

        return redirect()
            ->route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $purchaseOrder->getKey()])
            ->with('status', 'Solicitud de anulación de la orden N.º '.$purchaseOrder->numeroOc.' retirada.');
    }

    /**
     * Revisión del director en la plataforma (Autorizaciones compras → Anulaciones de órdenes).
     */
    public function show(Request $request, string $module, PurchaseOrder $purchaseOrder): View
    {
        abort_unless((int) $purchaseOrder->anulacion_aprobador_id === (int) $request->user()->id, 403);

        return view('modules.purchase-requests.approval.order-cancellation', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'order' => $this->loadOrder($purchaseOrder),
            'decideUrl' => route('purchase-requests.approval.order-cancellation.update', [
                'module' => $module,
                'purchase_order' => $purchaseOrder->getKey(),
            ]),
            'backUrl' => route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'anulaciones']),
        ]);
    }

    public function update(
        ResolvePurchaseOrderCancellationRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderCancellationService $service,
    ): RedirectResponse {
        $approve = $request->validated('decision') === 'aprobar';

        try {
            $service->resolve(
                $purchaseOrder,
                (int) $request->user()->id,
                $approve,
                $request->validated('comentario_anulacion'),
                'web',
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['comentario_anulacion' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('purchase-requests.approval.index', ['module' => $module, 'vista' => 'anulaciones'])
            ->with('status', 'Anulación de la orden N.º '.$purchaseOrder->numeroOc.' '.($approve ? 'aprobada' : 'rechazada').'.');
    }

    /**
     * Enlace firmado del correo (sin login).
     */
    public function emailShow(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderCancellationService $service): View
    {
        $this->forceUrlsFromRequest($request);
        $pending = $purchaseOrder->tieneAnulacionPendiente();

        // Si ya se resolvió o Compras la retiró, el enlace firmado solo informa.
        if ($pending) {
            $this->assertEmailDirector($request, $purchaseOrder);
        }

        return view('modules.purchase-requests.order-cancellation-email', [
            'order' => $this->loadOrder($purchaseOrder),
            'alreadyResolved' => ! $pending,
            'decideUrl' => $pending ? $service->emailUpdateUrl($purchaseOrder) : null,
            'result' => null,
        ]);
    }

    public function emailUpdate(
        ResolvePurchaseOrderCancellationRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderCancellationService $service,
    ): View|RedirectResponse {
        $this->forceUrlsFromRequest($request);
        $directorId = $this->assertEmailDirector($request, $purchaseOrder);
        $approve = $request->validated('decision') === 'aprobar';

        if ($purchaseOrder->tieneAnulacionPendiente()) {
            try {
                $service->resolve(
                    $purchaseOrder,
                    $directorId,
                    $approve,
                    $request->validated('comentario_anulacion'),
                    'email',
                );
            } catch (InvalidArgumentException $exception) {
                return back()->withErrors(['comentario_anulacion' => $exception->getMessage()])->withInput();
            }
        }

        return view('modules.purchase-requests.order-cancellation-email', [
            'order' => $this->loadOrder($purchaseOrder->refresh()),
            'alreadyResolved' => true,
            'decideUrl' => null,
            'result' => $approve ? 'aprobada' : 'rechazada',
        ]);
    }

    private function loadOrder(PurchaseOrder $order): PurchaseOrder
    {
        return $order->load(['items', 'source', 'anulacionAprobador', 'anulacionSolicitadaPor']);
    }

    private function assertEmailDirector(Request $request, PurchaseOrder $order): int
    {
        $directorId = (int) $request->query('director', $request->input('director'));
        abort_unless($directorId > 0 && (int) $order->anulacion_aprobador_id === $directorId, 403);

        return $directorId;
    }

    /** Los enlaces del formulario usan la misma URL con la que el director abrió el correo. */
    private function forceUrlsFromRequest(Request $request): void
    {
        URL::forceRootUrl($request->root());
        URL::forceScheme($request->getScheme());
    }
}
