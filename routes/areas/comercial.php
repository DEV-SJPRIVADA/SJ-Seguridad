<?php

use App\Http\Controllers\Comercial\CommercialClientController;
use App\Http\Controllers\Comercial\CommercialDashboardController;
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
        Route::get('/buscar', [CommercialClientController::class, 'search'])->name('search');
        Route::get('/crear', [CommercialClientController::class, 'create'])->name('create');
        Route::post('/', [CommercialClientController::class, 'store'])->name('store');
        Route::get('/{client}', [CommercialClientController::class, 'show'])->name('show');
        Route::get('/{client}/editar', [CommercialClientController::class, 'edit'])->name('edit');
        Route::patch('/{client}', [CommercialClientController::class, 'update'])->name('update');
    });

Route::middleware(['password.changed'])
    ->prefix('comercial/servicios')
    ->name('comercial.matriz.services.')
    ->group(function (): void {
        Route::get('/', [CommercialServiceController::class, 'index'])->name('index');
        Route::get('/crear', [CommercialServiceController::class, 'create'])->name('create');
        Route::post('/', [CommercialServiceController::class, 'store'])->name('store');
        Route::get('/{service}/editar', [CommercialServiceController::class, 'edit'])->name('edit');
        Route::patch('/{service}', [CommercialServiceController::class, 'update'])->name('update');
        Route::post('/{service}/inactivar', [CommercialServiceController::class, 'inactivate'])->name('inactivate');
    });
