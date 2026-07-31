<?php

use App\Http\Controllers\GestionHumana\FichaEmpleadosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/ficha-empleados/empleados')
    ->name('gestion-humana.ficha-empleados.employees.')
    ->group(function (): void {
        Route::get('/nuevo', [FichaEmpleadosController::class, 'create'])->name('create');
        Route::post('/nuevo', [FichaEmpleadosController::class, 'store'])->name('store');
        Route::get('/', [FichaEmpleadosController::class, 'index'])->name('index');
        Route::get('/exportar', [FichaEmpleadosController::class, 'exportExcel'])->name('export');
        Route::get('/plantilla-importacion', [FichaEmpleadosController::class, 'importTemplate'])->name('import-template');
        Route::get('/plantilla-importacion/exportar', [FichaEmpleadosController::class, 'exportImportTemplate'])->name('export-import-template');
        Route::post('/importar', [FichaEmpleadosController::class, 'import'])->name('import');
        Route::get('/{fichaEntry}/ficha', [FichaEmpleadosController::class, 'editFicha'])->name('ficha.edit');
        Route::patch('/{fichaEntry}/ficha', [FichaEmpleadosController::class, 'updateFicha'])->name('ficha.update');
        Route::patch('/{fichaEntry}/agregar', [FichaEmpleadosController::class, 'promote'])->name('promote');
    });
