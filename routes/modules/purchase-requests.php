<?php

use App\Http\Controllers\PurchaseRequests\PurchaseApprovalController;
use App\Http\Controllers\PurchaseRequests\PurchaseOrderCancellationController;
use App\Http\Controllers\PurchaseRequests\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequests\PurchaseOrderEvaluationController;
use App\Http\Controllers\PurchaseRequests\PurchaseProcessingController;
use App\Http\Controllers\PurchaseRequests\PurchaseProviderController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestController;
use App\Http\Controllers\PurchaseRequests\PurchaseTaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('purchase-requests/{module}')->name('purchase-requests.')->group(function (): void {
    Route::middleware(['purchase.tab:create'])->group(function (): void {
        Route::get('/nueva', [PurchaseRequestController::class, 'create'])->name('create');
        Route::post('/nueva', [PurchaseRequestController::class, 'store'])->name('store');
        Route::get('/nueva/plantilla-items', [PurchaseRequestController::class, 'importItemsTemplate'])->name('items.import-template');
        Route::post('/nueva/importar-items', [PurchaseRequestController::class, 'importItems'])->name('items.import');
    });

    Route::middleware(['purchase.tab:my_requests'])->group(function (): void {
        Route::get('/mis-solicitudes', [PurchaseRequestController::class, 'index'])->name('index');
        Route::get('/mis-solicitudes/{purchase_request}/editar', [PurchaseRequestController::class, 'edit'])->name('edit');
        Route::patch('/mis-solicitudes/{purchase_request}', [PurchaseRequestController::class, 'update'])->name('update');
    });

    Route::middleware(['purchase.tab:approval'])->group(function (): void {
        Route::get('/pendientes-autorizacion', [PurchaseApprovalController::class, 'index'])->name('approval.index');
        Route::patch('/solicitud/{purchase_request}/autorizar', [PurchaseApprovalController::class, 'update'])->name('approval.update');

        // Anulación de OC: la decide el director que Compras eligió.
        Route::get('/pendientes-autorizacion/anulaciones/{purchase_order}', [PurchaseOrderCancellationController::class, 'show'])->name('approval.order-cancellation.show');
        Route::patch('/pendientes-autorizacion/anulaciones/{purchase_order}', [PurchaseOrderCancellationController::class, 'update'])->name('approval.order-cancellation.update');
    });

    Route::middleware(['purchase.tab:processing'])->group(function (): void {
        Route::get('/bandeja-compras', [PurchaseProcessingController::class, 'index'])->name('processing.index');
        Route::get('/bandeja-compras/solicitud/{purchase_request}', [PurchaseProcessingController::class, 'editPurchase'])->name('processing.purchase');
        Route::patch('/bandeja-compras/solicitud/{purchase_request}', [PurchaseProcessingController::class, 'updatePurchase'])->name('processing.purchase.update');
        Route::post('/bandeja-compras/solicitud/{purchase_request}/cerrar-saldo', [PurchaseProcessingController::class, 'closePurchase'])->name('processing.purchase.close');
        Route::post('/bandeja-compras/solicitud/{purchase_request}/devolver', [PurchaseProcessingController::class, 'returnPurchase'])->name('processing.purchase.return');
        Route::get('/bandeja-compras/suministro/{supply_request}', [PurchaseProcessingController::class, 'editSupply'])->name('processing.supply');
        Route::patch('/bandeja-compras/suministro/{supply_request}', [PurchaseProcessingController::class, 'updateSupply'])->name('processing.supply.update');
        Route::post('/bandeja-compras/suministro/{supply_request}/cerrar-saldo', [PurchaseProcessingController::class, 'closeSupply'])->name('processing.supply.close');
        Route::post('/bandeja-compras/suministro/{supply_request}/devolver', [PurchaseProcessingController::class, 'returnSupply'])->name('processing.supply.return');
        Route::get('/bandeja-compras/suministro/{supply_request}/pdf', [PurchaseProcessingController::class, 'exportSupplyPdf'])->name('processing.supply.pdf');
        Route::get('/bandeja-compras/suministro/{supply_request}/excel', [PurchaseProcessingController::class, 'exportSupplyExcel'])->name('processing.supply.excel');

        // FO-AD-07: emisión de Orden de Compra desde la bandeja (compra y suministro).
        Route::get('/bandeja-compras/solicitud/{purchase_request}/orden-compra/crear', [PurchaseOrderController::class, 'createFromPurchase'])->name('orders.purchase.create');
        Route::post('/bandeja-compras/solicitud/{purchase_request}/orden-compra', [PurchaseOrderController::class, 'storeFromPurchase'])->name('orders.purchase.store');
        Route::get('/bandeja-compras/suministro/{supply_request}/orden-compra/crear', [PurchaseOrderController::class, 'createFromSupply'])->name('orders.supply.create');
        Route::post('/bandeja-compras/suministro/{supply_request}/orden-compra', [PurchaseOrderController::class, 'storeFromSupply'])->name('orders.supply.store');

        // Listado de órdenes de compra (tablero del área Compras).
        Route::get('/ordenes-compra', [PurchaseOrderController::class, 'index'])->name('orders.index');
        Route::get('/ordenes-compra/datatable', [PurchaseOrderController::class, 'datatable'])->name('orders.datatable');
        // Antes de /ordenes-compra/{purchase_order} para que "exportar" no se tome como número de OC.
        Route::get('/ordenes-compra/exportar', [PurchaseOrderController::class, 'export'])->name('orders.export');
        Route::get('/ordenes-compra/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('orders.show');
        Route::get('/ordenes-compra/{purchase_order}/pdf', [PurchaseOrderController::class, 'pdf'])->name('orders.pdf');
        Route::get('/ordenes-compra/{purchase_order}/excel', [PurchaseOrderController::class, 'excel'])->name('orders.excel');

        // Compras solicita (eligiendo director) o retira la anulación de una OC.
        Route::post('/ordenes-compra/{purchase_order}/anulacion', [PurchaseOrderCancellationController::class, 'request'])->name('orders.cancellation.request');
        Route::delete('/ordenes-compra/{purchase_order}/anulacion', [PurchaseOrderCancellationController::class, 'withdraw'])->name('orders.cancellation.withdraw');
    });

    Route::middleware(['purchase.tab:providers'])->group(function (): void {
        Route::get('/proveedores', [PurchaseProviderController::class, 'index'])->name('providers.index');
        Route::get('/proveedores/datatable', [PurchaseProviderController::class, 'datatable'])->name('providers.datatable');
        Route::get('/proveedores/exportar', [PurchaseProviderController::class, 'export'])->name('providers.export');
        Route::get('/proveedores/plantilla', [PurchaseProviderController::class, 'template'])->name('providers.template');
        // Catálogos → IVA (tasas para las OC). Ver con el permiso de Proveedores.
        Route::get('/catalogos/iva', [PurchaseTaxRateController::class, 'index'])->name('tax-rates.index');
    });

    Route::middleware(['purchase.tab:providers.edit'])->group(function (): void {
        Route::get('/proveedores/crear', [PurchaseProviderController::class, 'create'])->name('providers.create');
        Route::post('/proveedores', [PurchaseProviderController::class, 'store'])->name('providers.store');
        Route::post('/proveedores/importar', [PurchaseProviderController::class, 'import'])->name('providers.import');
        Route::get('/proveedores/importar/reporte/{token}', [PurchaseProviderController::class, 'downloadImportReport'])->name('providers.import-report');
        Route::get('/proveedores/{purchase_provider}/editar', [PurchaseProviderController::class, 'edit'])->name('providers.edit');
        Route::patch('/proveedores/{purchase_provider}', [PurchaseProviderController::class, 'update'])->name('providers.update');

        // Catálogos → IVA: crear y editar tasas (edición de Proveedores).
        Route::post('/catalogos/iva', [PurchaseTaxRateController::class, 'store'])->name('tax-rates.store');
        Route::patch('/catalogos/iva/{purchase_tax_rate}', [PurchaseTaxRateController::class, 'update'])->name('tax-rates.update');

        // Evaluación post-entrega: fuera del grupo processing. Quien solo procesa no marca recibido.
        Route::get('/ordenes-compra/{purchase_order}/evaluacion', [PurchaseOrderEvaluationController::class, 'edit'])->name('orders.evaluation.edit');
        Route::put('/ordenes-compra/{purchase_order}/evaluacion', [PurchaseOrderEvaluationController::class, 'update'])->name('orders.evaluation.update');
    });

    Route::get('/solicitud/{purchase_request}/pdf', [PurchaseRequestController::class, 'exportPdf'])->name('export.pdf');
    // PDF de las OC de la solicitud para quien ve la solicitud (fuera del grupo de Compras; la OC debe ser de esa solicitud).
    Route::get('/solicitud/{purchase_request}/ordenes/{purchase_order}/pdf', [PurchaseRequestController::class, 'orderPdf'])->name('source-orders.pdf');
    Route::get('/solicitud/{purchase_request}/excel', [PurchaseRequestController::class, 'exportExcel'])->name('export.excel');
    Route::get('/solicitud/{purchase_request}/adjuntos/{attachment}', [PurchaseRequestController::class, 'downloadAttachment'])
        ->scopeBindings()
        ->name('attachments.download');
    Route::post('/solicitud/{purchase_request}/comentarios', [PurchaseRequestController::class, 'storeComment'])
        ->name('comments.store');
    Route::get('/solicitud/{purchase_request}', [PurchaseRequestController::class, 'show'])->name('show');
});
