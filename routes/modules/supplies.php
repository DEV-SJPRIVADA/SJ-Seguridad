<?php

use App\Http\Controllers\Supplies\SupplyProductController;
use App\Http\Controllers\Supplies\SupplyRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('supplies/{module}')->name('supplies.')->group(function () {
    Route::middleware(['supply.tab:my_requests'])->group(function () {
        Route::get('/mis-solicitudes', [SupplyRequestController::class, 'index'])->name('index');
        Route::get('/mis-solicitudes/exportar', [SupplyRequestController::class, 'exportExcel'])->name('export');
        Route::get('/solicitar', [SupplyRequestController::class, 'create'])->name('create');
        Route::post('/solicitar', [SupplyRequestController::class, 'store'])->name('store');
        Route::get('/mis-solicitudes/{supply_request}/editar', [SupplyRequestController::class, 'edit'])->name('edit');
        Route::patch('/mis-solicitudes/{supply_request}', [SupplyRequestController::class, 'update'])->name('update');
    });

    Route::post('/solicitud/{supply_request}/comentarios', [SupplyRequestController::class, 'storeComment'])->name('comments.store');
    Route::get('/solicitud/{supply_request}', [SupplyRequestController::class, 'show'])->name('show');
    Route::get('/solicitud/{supply_request}/pdf', [SupplyRequestController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/solicitud/{supply_request}/excel', [SupplyRequestController::class, 'exportExcelRequest'])->name('export.excel');
    // PDF de las OC del suministro para quien ve el suministro (la OC debe ser de ese suministro).
    Route::get('/solicitud/{supply_request}/ordenes/{purchase_order}/pdf', [SupplyRequestController::class, 'orderPdf'])->name('orders.pdf');

    Route::middleware(['supply.tab:quality'])->group(function () {
        Route::get('/aprobacion-insumos', [SupplyRequestController::class, 'approvalIndex'])->name('approval.index');
        Route::get('/aprobacion-insumos/exportar', [SupplyRequestController::class, 'approvalExport'])->name('approval.export');
        Route::get('/aprobacion-insumos/{supply_request}/editar', [SupplyRequestController::class, 'approvalEdit'])->name('approval.edit');
        Route::patch('/aprobacion-insumos/{supply_request}', [SupplyRequestController::class, 'approvalUpdate'])->name('approval.update');
        Route::get('/insumos-aprobados', [SupplyRequestController::class, 'approvedIndex'])->name('approved.index');
        Route::get('/insumos-aprobados/exportar', [SupplyRequestController::class, 'approvedExportAll'])->name('approved.export-all');
        Route::get('/insumos-aprobados/{supply_request}/exportar', [SupplyRequestController::class, 'approvedExport'])->name('approved.export');
    });

    Route::middleware(['supply.tab:catalog'])->group(function () {
        Route::get('/catalogo', [SupplyProductController::class, 'index'])->name('products.index');
        Route::get('/catalogo/exportar', [SupplyProductController::class, 'exportExcel'])->name('products.export');
        Route::post('/catalogo', [SupplyProductController::class, 'store'])->name('products.store');
        Route::patch('/catalogo/{product}', [SupplyProductController::class, 'update'])->name('products.update');
    });
});
