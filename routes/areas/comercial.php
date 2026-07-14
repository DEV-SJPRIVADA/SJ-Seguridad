<?php

use App\Http\Controllers\Comercial\CommercialClientController;
use App\Http\Controllers\Comercial\CommercialServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('comercial/matriz-clientes')
    ->name('comercial.matriz.')
    ->group(function (): void {
        Route::get('/', [CommercialClientController::class, 'index'])->name('clients.index');
        Route::get('/crear', [CommercialClientController::class, 'create'])->name('clients.create');
        Route::post('/', [CommercialClientController::class, 'store'])->name('clients.store');
        Route::get('/{client}', [CommercialClientController::class, 'show'])->name('clients.show');
        Route::get('/{client}/editar', [CommercialClientController::class, 'edit'])->name('clients.edit');
        Route::patch('/{client}', [CommercialClientController::class, 'update'])->name('clients.update');

        Route::get('/{client}/servicios/crear', [CommercialServiceController::class, 'create'])->name('services.create');
        Route::post('/{client}/servicios', [CommercialServiceController::class, 'store'])->name('services.store');
        Route::get('/{client}/servicios/{service}/editar', [CommercialServiceController::class, 'edit'])->name('services.edit');
        Route::patch('/{client}/servicios/{service}', [CommercialServiceController::class, 'update'])->name('services.update');
        Route::post('/{client}/servicios/{service}/inactivar', [CommercialServiceController::class, 'inactivate'])->name('services.inactivate');
    });
