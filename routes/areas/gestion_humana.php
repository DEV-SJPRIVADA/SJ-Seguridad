<?php

use App\Http\Controllers\GestionHumana\FichaEmpleadosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/ficha-empleados/empleados')
    ->name('gestion-humana.ficha-empleados.employees.')
    ->group(function (): void {
        Route::get('/', [FichaEmpleadosController::class, 'index'])->name('index');
        Route::get('/exportar', [FichaEmpleadosController::class, 'exportExcel'])->name('export');
        Route::patch('/{fichaEntry}/agregar', [FichaEmpleadosController::class, 'promote'])->name('promote');
    });
