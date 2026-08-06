<?php

use App\Http\Controllers\Comercial\CommercialClientChecklistController;
use App\Http\Controllers\Comercial\CommercialClientController;
use App\Http\Controllers\Comercial\CommercialDashboardController;
use App\Http\Controllers\Comercial\CommercialParameterController;
use App\Http\Controllers\Comercial\CommercialServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])->group(function (): void {
    Route::get('comercial/dashboard', CommercialDashboardController::class)->name('comercial.dashboard');
});

Route::middleware(['password.changed'])
    ->prefix('comercial/clientes')
    ->name('comercial.matriz.clients.')
    ->group(function (): void {
        Route::get('/', [CommercialClientController::class, 'index'])->name('index');
        Route::get('/exportar', [CommercialClientController::class, 'exportExcel'])->name('export');
        Route::get('/plantilla-importacion', [CommercialClientController::class, 'importTemplate'])->name('import-template');
        Route::get('/plantilla-importacion/exportar', [CommercialClientController::class, 'exportImportTemplate'])->name('export-import-template');
        Route::post('/importar', [CommercialClientController::class, 'import'])->name('import');
        Route::get('/importar/reporte/{token}', [CommercialClientController::class, 'downloadImportReport'])->name('import-report');
        Route::get('/checklist-documental', [CommercialClientChecklistController::class, 'index'])->name('checklist.index');
        Route::get('/checklist-documental/exportar', [CommercialClientChecklistController::class, 'exportExcel'])->name('checklist.export');
        Route::get('/buscar', [CommercialClientController::class, 'search'])->name('search');
        Route::get('/crear', [CommercialClientController::class, 'create'])->name('create');
        Route::post('/', [CommercialClientController::class, 'store'])->name('store');
        Route::patch('/{client}/checklist-documental', [CommercialClientChecklistController::class, 'update'])->name('checklist.update');
        Route::get('/{client}', [CommercialClientController::class, 'show'])->name('show');
        Route::get('/{client}/editar', [CommercialClientController::class, 'edit'])->name('edit');
        Route::patch('/{client}', [CommercialClientController::class, 'update'])->name('update');
    });

Route::middleware(['password.changed'])
    ->prefix('comercial/servicios')
    ->name('comercial.matriz.services.')
    ->group(function (): void {
        Route::get('/', [CommercialServiceController::class, 'index'])->name('index');
        Route::get('/exportar', [CommercialServiceController::class, 'exportExcel'])->name('export');
        Route::get('/crear', [CommercialServiceController::class, 'create'])->name('create');
        Route::post('/', [CommercialServiceController::class, 'store'])->name('store');
        Route::get('/{service}/editar', [CommercialServiceController::class, 'edit'])->name('edit');
        Route::patch('/{service}', [CommercialServiceController::class, 'update'])->name('update');
        Route::post('/{service}/inactivar', [CommercialServiceController::class, 'inactivate'])->name('inactivate');
        Route::post('/{service}/activar', [CommercialServiceController::class, 'activate'])->name('activate');
    });

Route::middleware(['password.changed'])
    ->prefix('comercial/parametros')
    ->name('comercial.parameters.')
    ->group(function (): void {
        Route::get('/', [CommercialParameterController::class, 'index'])->name('index');
        Route::post('/{type}', [CommercialParameterController::class, 'store'])->name('store');
        Route::patch('/{type}/{parameterId}', [CommercialParameterController::class, 'update'])->name('update');
        Route::delete('/{type}/{parameterId}', [CommercialParameterController::class, 'destroy'])->name('destroy');
    });
