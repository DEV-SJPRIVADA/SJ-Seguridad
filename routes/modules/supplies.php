<?php

use App\Http\Controllers\Supplies\SupplyRequestController;
use App\Http\Controllers\Supplies\SupplyProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('supplies/{module}')->name('supplies.')->group(function () {
    // Tablero principal y Mis Solicitudes
    Route::get('/mis-solicitudes', [SupplyRequestController::class, 'index'])->name('index');
    Route::get('/solicitud/{supply_request}', [SupplyRequestController::class, 'show'])->name('show');
    Route::get('/solicitar', [SupplyRequestController::class, 'create'])->name('create');
    Route::post('/solicitar', [SupplyRequestController::class, 'store'])->name('store');
    
    // Revisión Calidad
    Route::get('/revision-calidad', [SupplyRequestController::class, 'qualityIndex'])->name('quality.index');
    Route::get('/revision-calidad/{supply_request}/editar', [SupplyRequestController::class, 'qualityEdit'])->name('quality.edit');
    Route::patch('/revision-calidad/{supply_request}', [SupplyRequestController::class, 'qualityUpdate'])->name('quality.update');
    
    // Gestión Compras
    Route::get('/gestion-compras', [SupplyRequestController::class, 'purchasingIndex'])->name('purchasing.index');
    Route::get('/gestion-compras/{supply_request}/costear', [SupplyRequestController::class, 'purchasingEdit'])->name('purchasing.edit');
    Route::patch('/gestion-compras/{supply_request}', [SupplyRequestController::class, 'purchasingUpdate'])->name('purchasing.update');
    
    // Catálogo (Administrable)
    Route::get('/catalogo', [SupplyProductController::class, 'index'])->name('products.index');
    Route::post('/catalogo', [SupplyProductController::class, 'store'])->name('products.store');
    Route::patch('/catalogo/{product}', [SupplyProductController::class, 'update'])->name('products.update');
});
