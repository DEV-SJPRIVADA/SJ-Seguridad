<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\SavePurchaseTaxRateRequest;
use App\Models\PurchaseTaxRate;
use App\Services\Access\PurchaseAccessService;
use App\Services\PurchaseRequests\PurchaseRequestAuditLogService;
use App\Traits\HasCatalogTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo de tasas de IVA de las órdenes de compra (Compras → Catálogos → IVA).
 * Ver: `purchase.providers.view`; crear / editar / activar: `purchase.providers.edit`.
 * Las tasas no se borran: se inactivan para no afectar órdenes ya emitidas.
 */
class PurchaseTaxRateController extends Controller
{
    use HasCatalogTabs;

    public function index(string $module, Request $request, PurchaseAccessService $access): View
    {
        $editing = null;

        if ($request->filled('editar')) {
            $editing = PurchaseTaxRate::query()->find((int) $request->query('editar'));
        }

        return view('modules.purchase-requests.tax-rates.index', [
            'module' => $module,
            'subTabs' => $this->getCatalogSubTabs($module),
            'rates' => PurchaseTaxRate::query()->orderBy('porcentaje')->get(),
            'canEdit' => $access->canEditProviders($request->user()),
            'editing' => $editing,
        ]);
    }

    public function store(SavePurchaseTaxRateRequest $request, string $module, PurchaseRequestAuditLogService $audit): RedirectResponse
    {
        $rate = PurchaseTaxRate::query()->create($request->validated());

        $audit->logModelChange(
            eventType: 'purchase_tax_rate',
            action: 'created',
            model: $rate,
            before: null,
            after: $rate->only(['nombre', 'porcentaje', 'activo']),
        );

        return redirect()
            ->route('purchase-requests.tax-rates.index', ['module' => $module])
            ->with('status', 'Tasa de IVA '.PurchaseTaxRate::formatPorcentaje($rate->porcentaje).' creada.');
    }

    public function update(
        SavePurchaseTaxRateRequest $request,
        string $module,
        PurchaseTaxRate $purchaseTaxRate,
        PurchaseRequestAuditLogService $audit,
    ): RedirectResponse {
        $before = $purchaseTaxRate->only(['nombre', 'porcentaje', 'activo']);
        $purchaseTaxRate->update($request->validated());

        $audit->logModelChange(
            eventType: 'purchase_tax_rate',
            action: 'updated',
            model: $purchaseTaxRate,
            before: $before,
            after: $purchaseTaxRate->only(['nombre', 'porcentaje', 'activo']),
        );

        return redirect()
            ->route('purchase-requests.tax-rates.index', ['module' => $module])
            ->with('status', 'Tasa de IVA '.PurchaseTaxRate::formatPorcentaje($purchaseTaxRate->porcentaje).' actualizada.');
    }
}
