<?php

use App\Http\Controllers\Calidad\PlaneacionEstrategicaController;
use App\Http\Controllers\Calidad\ServicioClienteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('calidad/servicio-cliente')
    ->name('calidad.servicio-cliente.')
    ->group(function (): void {
        Route::get('/', [ServicioClienteController::class, 'index'])->name('index');
        Route::get('/dashboard', [ServicioClienteController::class, 'dashboard'])->name('dashboard');

        Route::get('/fo-gi-22', [ServicioClienteController::class, 'foGi22'])->name('fo-gi-22.index');
        Route::get('/fo-gi-22/datatable', [ServicioClienteController::class, 'datatable'])->name('fo-gi-22.datatable');
        Route::get('/fo-gi-22/exportar', [ServicioClienteController::class, 'export'])->name('fo-gi-22.export');
        Route::get('/fo-gi-22/historial', [ServicioClienteController::class, 'historial'])->name('fo-gi-22.historial');
        Route::get('/fo-gi-22/crear', [ServicioClienteController::class, 'create'])->name('fo-gi-22.create');
        Route::post('/fo-gi-22', [ServicioClienteController::class, 'store'])->name('fo-gi-22.store');
        Route::get('/fo-gi-22/{caso}', [ServicioClienteController::class, 'show'])->name('fo-gi-22.show');
        Route::patch('/fo-gi-22/{caso}', [ServicioClienteController::class, 'update'])->name('fo-gi-22.update');
        Route::delete('/fo-gi-22/{caso}', [ServicioClienteController::class, 'destroy'])->name('fo-gi-22.destroy');
        Route::post('/fo-gi-22/{caso}/observaciones', [ServicioClienteController::class, 'storeObservacion'])->name('fo-gi-22.observaciones.store');
        Route::patch('/fo-gi-22/{caso}/observaciones/{observacion}', [ServicioClienteController::class, 'updateObservacion'])->name('fo-gi-22.observaciones.update');
        Route::delete('/fo-gi-22/{caso}/observaciones/{observacion}', [ServicioClienteController::class, 'destroyObservacion'])->name('fo-gi-22.observaciones.destroy');
        Route::post('/fo-gi-22/{caso}/adjuntos', [ServicioClienteController::class, 'storeAdjuntos'])->name('fo-gi-22.adjuntos.store');
        Route::get('/fo-gi-22/{caso}/adjuntos/{adjunto}', [ServicioClienteController::class, 'downloadAdjunto'])->name('fo-gi-22.adjuntos.download');
        Route::delete('/fo-gi-22/{caso}/adjuntos/{adjunto}', [ServicioClienteController::class, 'destroyAdjunto'])->name('fo-gi-22.adjuntos.destroy');

        Route::get('/catalogos', [ServicioClienteController::class, 'catalogos'])->name('catalogos');
        Route::post('/catalogos/{type}', [ServicioClienteController::class, 'storeCatalog'])->name('catalogos.store');
        Route::patch('/catalogos/{type}/{item}', [ServicioClienteController::class, 'updateCatalog'])->name('catalogos.update');
        Route::delete('/catalogos/{type}/{item}', [ServicioClienteController::class, 'destroyCatalog'])->name('catalogos.destroy');
    });

Route::middleware(['password.changed'])
    ->prefix('calidad/planeacion')
    ->name('calidad.planeacion.')
    ->group(function (): void {
        Route::get('/', [PlaneacionEstrategicaController::class, 'index'])->name('index');
        Route::get('/dashboard', [PlaneacionEstrategicaController::class, 'dashboard'])->name('dashboard');
        Route::get('/captura', [PlaneacionEstrategicaController::class, 'captura'])->name('captura');
        Route::get('/captura/datatable', [PlaneacionEstrategicaController::class, 'capturaDatatable'])->name('captura.datatable');
        Route::get('/captura/{indicator}', [PlaneacionEstrategicaController::class, 'capturaShow'])->name('captura.show')->whereNumber('indicator');
        Route::post('/captura/{indicator}', [PlaneacionEstrategicaController::class, 'capturaStore'])->name('captura.store')->whereNumber('indicator');

        Route::get('/indicadores', [PlaneacionEstrategicaController::class, 'indicadores'])->name('indicadores');
        Route::get('/indicadores/datatable', [PlaneacionEstrategicaController::class, 'datatable'])->name('indicadores.datatable');
        Route::post('/indicadores/previsualizar-formula', [PlaneacionEstrategicaController::class, 'previewFormula'])->name('indicadores.preview');
        Route::get('/indicadores/crear', [PlaneacionEstrategicaController::class, 'create'])->name('indicadores.create');
        Route::post('/indicadores', [PlaneacionEstrategicaController::class, 'store'])->name('indicadores.store');
        Route::get('/indicadores/{indicator}/editar', [PlaneacionEstrategicaController::class, 'edit'])->name('indicadores.edit')->whereNumber('indicator');
        Route::patch('/indicadores/{indicator}', [PlaneacionEstrategicaController::class, 'update'])->name('indicadores.update')->whereNumber('indicator');
        Route::post('/indicadores/{indicator}/publicar', [PlaneacionEstrategicaController::class, 'publish'])->name('indicadores.publish')->whereNumber('indicator');
        Route::post('/indicadores/{indicator}/archivar', [PlaneacionEstrategicaController::class, 'archive'])->name('indicadores.archive')->whereNumber('indicator');
        Route::post('/indicadores/{indicator}/restablecer', [PlaneacionEstrategicaController::class, 'restore'])->name('indicadores.restore')->whereNumber('indicator');
        Route::post('/indicadores/{indicator}/compartir', [PlaneacionEstrategicaController::class, 'share'])->name('indicadores.share')->whereNumber('indicator');
        Route::get('/indicadores/{indicator}/exportar-excel', [PlaneacionEstrategicaController::class, 'exportFichaExcel'])->name('indicadores.export.excel')->whereNumber('indicator');
        Route::get('/indicadores/{indicator}/exportar-pdf', [PlaneacionEstrategicaController::class, 'exportFichaPdf'])->name('indicadores.export.pdf')->whereNumber('indicator');
        Route::get('/indicadores/{indicator}', [PlaneacionEstrategicaController::class, 'show'])->name('indicadores.show')->whereNumber('indicator');
    });
