<?php

use App\Http\Controllers\GestionHumana\ArchivoController;
use App\Http\Controllers\GestionHumana\FichaEmpleadosCatalogController;
use App\Http\Controllers\GestionHumana\FichaEmpleadosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/ficha-empleados/catalogos')
    ->name('gestion-humana.ficha-empleados.catalogs.')
    ->group(function (): void {
        Route::get('/', [FichaEmpleadosCatalogController::class, 'index'])->name('index');
        Route::post('/{type}', [FichaEmpleadosCatalogController::class, 'store'])->name('store');
        Route::patch('/{type}/{item}', [FichaEmpleadosCatalogController::class, 'update'])->name('update');
        Route::delete('/{type}/{item}', [FichaEmpleadosCatalogController::class, 'destroy'])->name('destroy');
    });

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
        Route::get('/exportar-archivo', [FichaEmpleadosController::class, 'exportArchiveTemplate'])->name('export-archive-template');
        Route::post('/importar', [FichaEmpleadosController::class, 'import'])->name('import');
        Route::get('/importar/reporte/{token}', [FichaEmpleadosController::class, 'downloadImportReport'])->name('import-report');
        Route::get('/{fichaEntry}/ficha', [FichaEmpleadosController::class, 'editFicha'])->name('ficha.edit');
        Route::patch('/{fichaEntry}/ficha', [FichaEmpleadosController::class, 'updateFicha'])->name('ficha.update');
    });

Route::middleware(['password.changed'])
    ->prefix('gestion-humana/archivo')
    ->name('gestion-humana.archivo.')
    ->group(function (): void {
        Route::get('/', [ArchivoController::class, 'index'])->name('index');
        Route::post('/importar', [ArchivoController::class, 'import'])->name('import');
        Route::get('/importar/reporte/{token}', [ArchivoController::class, 'downloadImportReport'])->name('import-report');
        Route::patch('/{fichaEntry}', [ArchivoController::class, 'update'])->name('update');
    });
