<?php

use App\Http\Controllers\Supplies\SupplyProductController;
use App\Http\Controllers\Supplies\SupplyRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('supplies/{module}')->name('supplies.')->group(function () {
    Route::middleware(['supply.tab:my_requests'])->group(function () {
        Route::get('/mis-solicitudes', [SupplyRequestController::class, 'index'])->name('index');
        Route::get('/solicitud/{supply_request}', [SupplyRequestController::class, 'show'])->name('show');
        Route::get('/solicitar', [SupplyRequestController::class, 'create'])->name('create');
        Route::post('/solicitar', [SupplyRequestController::class, 'store'])->name('store');
    });

    Route::middleware(['supply.tab:quality'])->group(function () {
        Route::get('/aprobacion-insumos', [SupplyRequestController::class, 'approvalIndex'])->name('approval.index');
        Route::get('/aprobacion-insumos/{supply_request}/editar', [SupplyRequestController::class, 'approvalEdit'])->name('approval.edit');
        Route::patch('/aprobacion-insumos/{supply_request}', [SupplyRequestController::class, 'approvalUpdate'])->name('approval.update');
    });

    Route::middleware(['supply.tab:catalog'])->group(function () {
        Route::get('/catalogo', [SupplyProductController::class, 'index'])->name('products.index');
        Route::post('/catalogo', [SupplyProductController::class, 'store'])->name('products.store');
        Route::patch('/catalogo/{product}', [SupplyProductController::class, 'update'])->name('products.update');
    });
});
