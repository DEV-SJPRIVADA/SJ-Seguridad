<?php

use App\Http\Controllers\PurchaseRequests\PurchaseApprovalController;
use App\Http\Controllers\PurchaseRequests\PurchaseProcessingController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('purchase-requests/{module}')->name('purchase-requests.')->group(function (): void {
    Route::middleware(['purchase.tab:create'])->group(function (): void {
        Route::get('/nueva', [PurchaseRequestController::class, 'create'])->name('create');
        Route::post('/nueva', [PurchaseRequestController::class, 'store'])->name('store');
    });

    Route::middleware(['purchase.tab:my_requests'])->group(function (): void {
        Route::get('/mis-solicitudes', [PurchaseRequestController::class, 'index'])->name('index');
        Route::get('/mis-solicitudes/{purchase_request}/editar', [PurchaseRequestController::class, 'edit'])->name('edit');
        Route::patch('/mis-solicitudes/{purchase_request}', [PurchaseRequestController::class, 'update'])->name('update');
    });

    Route::middleware(['purchase.tab:approval'])->group(function (): void {
        Route::get('/pendientes-autorizacion', [PurchaseApprovalController::class, 'index'])->name('approval.index');
        Route::patch('/solicitud/{purchase_request}/autorizar', [PurchaseApprovalController::class, 'update'])->name('approval.update');
    });

    Route::middleware(['purchase.tab:processing'])->group(function (): void {
        Route::get('/bandeja-compras', [PurchaseProcessingController::class, 'index'])->name('processing.index');
        Route::get('/bandeja-compras/solicitud/{purchase_request}', [PurchaseProcessingController::class, 'editPurchase'])->name('processing.purchase');
        Route::patch('/bandeja-compras/solicitud/{purchase_request}', [PurchaseProcessingController::class, 'updatePurchase'])->name('processing.purchase.update');
        Route::get('/bandeja-compras/suministro/{supply_request}', [PurchaseProcessingController::class, 'editSupply'])->name('processing.supply');
        Route::patch('/bandeja-compras/suministro/{supply_request}', [PurchaseProcessingController::class, 'updateSupply'])->name('processing.supply.update');
        Route::get('/bandeja-compras/suministro/{supply_request}/pdf', [PurchaseProcessingController::class, 'exportSupplyPdf'])->name('processing.supply.pdf');
        Route::get('/bandeja-compras/suministro/{supply_request}/excel', [PurchaseProcessingController::class, 'exportSupplyExcel'])->name('processing.supply.excel');
    });

    Route::get('/solicitud/{purchase_request}/pdf', [PurchaseRequestController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/solicitud/{purchase_request}/excel', [PurchaseRequestController::class, 'exportExcel'])->name('export.excel');
    Route::get('/solicitud/{purchase_request}/adjuntos/{attachment}', [PurchaseRequestController::class, 'downloadAttachment'])
        ->scopeBindings()
        ->name('attachments.download');
    Route::get('/solicitud/{purchase_request}', [PurchaseRequestController::class, 'show'])->name('show');
});
