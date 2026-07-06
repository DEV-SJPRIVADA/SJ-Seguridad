<?php

use App\Http\Controllers\Requisitions\RequisitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('requisitions/{module}')->name('requisitions.')->group(function () {
    Route::get('/dashboard', [RequisitionController::class, 'dashboard'])->name('dashboard');
    Route::get('/solicitar', [RequisitionController::class, 'create'])->name('create');
    Route::post('/solicitar', [RequisitionController::class, 'store'])->name('store');
    Route::get('/seguimiento', [RequisitionController::class, 'tracking'])->name('tracking');
    Route::get('/gestion', [RequisitionController::class, 'manage'])->name('manage');
    Route::get('/gestion/{requisition}/editar', [RequisitionController::class, 'edit'])->name('edit');
    Route::get('/gestion/{requisition}/imprimir', [RequisitionController::class, 'print'])->name('print');
    Route::patch('/gestion/{requisition}', [RequisitionController::class, 'update'])->name('update');
    Route::get('/parametros', [RequisitionController::class, 'parameters'])->name('parameters');
    Route::post('/parametros/{type}', [RequisitionController::class, 'storeParameter'])->name('parameters.store');
    Route::patch('/parametros/{type}/{parameterId}', [RequisitionController::class, 'updateParameter'])->name('parameters.update');
    Route::delete('/parametros/{type}/{parameterId}', [RequisitionController::class, 'destroyParameter'])->name('parameters.destroy');
});
