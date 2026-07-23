<?php

use App\Http\Controllers\Operaciones\IndicadorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])
    ->prefix('operaciones/indicadores')
    ->name('indicadores.')
    ->group(function (): void {
        Route::get('/dashboard', [IndicadorController::class, 'dashboard'])
            ->middleware(['indicador.tab:dashboard'])
            ->name('dashboard');

        Route::middleware(['indicador.tab:capture'])->group(function (): void {
            Route::get('/captura', [IndicadorController::class, 'index'])->name('index');
            Route::get('/captura/{indicator:code}', [IndicadorController::class, 'show'])->name('show');
            Route::post('/captura/{indicator:code}', [IndicadorController::class, 'storeCapture'])->name('capture.store');
        });

        Route::middleware(['can:operations.export'])->group(function (): void {
            Route::get('/exportar/pdf', [IndicadorController::class, 'exportDashboardPdf'])->name('export.dashboard.pdf');
            Route::get('/exportar/informe-gestion.pptx', [IndicadorController::class, 'exportManagementPptx'])->name('export.management.pptx');
            Route::get('/exportar/captura/{indicator:code}/excel', [IndicadorController::class, 'exportLeaderExcel'])->name('export.leader.excel');
            Route::get('/exportar/captura/{indicator:code}/pdf', [IndicadorController::class, 'exportLeaderPdf'])->name('export.leader.pdf');
            Route::get('/exportar/consolidado/{indicator:code}/excel', [IndicadorController::class, 'exportConsolidadoExcel'])->name('export.consolidado.excel');
            Route::get('/exportar/consolidado/{indicator:code}/pdf', [IndicadorController::class, 'exportConsolidadoPdf'])->name('export.consolidado.pdf');
        });

        Route::middleware(['indicador.tab:manage'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/ajustes', [IndicadorController::class, 'ajustes'])->name('ajustes');

            Route::get('/periodos', [IndicadorController::class, 'periods'])->name('periods.index');
            Route::post('/periodos', [IndicadorController::class, 'storePeriod'])->name('periods.store');
            Route::post('/periodos/{period}/cerrar', [IndicadorController::class, 'closePeriod'])->name('periods.close');
            Route::post('/periodos/{period}/reabrir', [IndicadorController::class, 'reopenPeriod'])->name('periods.reopen');

            Route::get('/metas', [IndicadorController::class, 'metas'])->name('metas');
            Route::patch('/metas', [IndicadorController::class, 'updateMetas'])->name('metas.update');
            Route::get('/pesos', [IndicadorController::class, 'weights'])->name('weights');
            Route::patch('/pesos', [IndicadorController::class, 'updateMetas'])->name('weights.update');

            Route::patch('/capturadores/{user}', [IndicadorController::class, 'updateCapturador'])->name('capturadores.update');
            Route::get('/capturadores', [IndicadorController::class, 'capturadores'])->name('capturadores');

            Route::get('/consolidado', [IndicadorController::class, 'consolidado'])->name('consolidado.index');
            Route::get('/consolidado/{indicator:code}', [IndicadorController::class, 'consolidadoShow'])->name('consolidado.show');

            Route::get('/auditoria', [IndicadorController::class, 'auditLog'])->name('audit.index');

            Route::post('/dashboard/resumen', [IndicadorController::class, 'saveSummary'])->name('dashboard.summary');
        });
    });
