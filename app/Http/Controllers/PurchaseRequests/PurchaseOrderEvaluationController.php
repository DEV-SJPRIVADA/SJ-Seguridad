<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseOrderEvaluationRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseRequests\PurchaseOrderEvaluationService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseOrderEvaluationController extends Controller
{
    use HasPurchaseTabs;

    /**
     * Pantalla de recibido y calificación, ligada al detalle de la OC.
     */
    public function edit(string $module, PurchaseOrder $purchaseOrder): View
    {
        $this->assertEvaluable($module, $purchaseOrder);
        $purchaseOrder->load(['evaluation', 'provider']);

        $back = $this->backLink($module, $purchaseOrder);

        return view('modules.purchase-requests.orders.evaluation', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
            'order' => $purchaseOrder,
            'evaluation' => $purchaseOrder->evaluation,
            'backUrl' => $back['url'],
            'backLabel' => $back['label'],
        ]);
    }

    public function update(
        UpdatePurchaseOrderEvaluationRequest $request,
        string $module,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderEvaluationService $service,
    ): RedirectResponse {
        $service->save($purchaseOrder, $request->validated(), $request->user());

        return redirect()
            ->route('purchase-requests.orders.evaluation.edit', [
                'module' => $module,
                'purchase_order' => $purchaseOrder->getKey(),
            ])
            ->with('status', 'Evaluación guardada.');
    }

    private function assertEvaluable(string $module, PurchaseOrder $order): void
    {
        $user = auth()->user();

        abort_unless($user !== null && $user->canAccessPurchaseTab($module, 'providers.edit'), 403);
        abort_unless($order->estado === 'emitida', 404);
    }

    /**
     * @return array{url: string, label: string}
     */
    private function backLink(string $module, PurchaseOrder $order): array
    {
        $user = auth()->user();

        if ($user?->canAccessPurchaseTab($module, 'processing')) {
            return [
                'url' => route('purchase-requests.orders.show', [
                    'module' => $module,
                    'purchase_order' => $order->getKey(),
                ]),
                'label' => 'Volver a la orden',
            ];
        }

        return [
            'url' => route('purchase-requests.providers.index', ['module' => $module]),
            'label' => 'Volver a catálogos',
        ];
    }
}
