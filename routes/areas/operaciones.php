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
            Route::get('/exportar/captura/{indicator:code}/excel', [IndicadorController::class, 'exportLeaderExcel'])->name('export.leader.excel');
            Route::get('/exportar/captura/{indicator:code}/pdf', [IndicadorController::class, 'exportLeaderPdf'])->name('export.leader.pdf');
            Route::get('/exportar/madre/{indicator:code}/excel', [IndicadorController::class, 'exportMotherExcel'])->name('export.mother.excel');
            Route::get('/exportar/madre/{indicator:code}/pdf', [IndicadorController::class, 'exportMotherPdf'])->name('export.mother.pdf');
        });

        Route::middleware(['indicador.tab:manage'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/periodos', [IndicadorController::class, 'periods'])->name('periods.index');
            Route::post('/periodos', [IndicadorController::class, 'storePeriod'])->name('periods.store');
            Route::post('/periodos/{period}/cerrar', [IndicadorController::class, 'closePeriod'])->name('periods.close');
            Route::post('/periodos/{period}/reabrir', [IndicadorController::class, 'reopenPeriod'])->name('periods.reopen');

            Route::get('/pesos', [IndicadorController::class, 'weights'])->name('weights');
            Route::patch('/pesos', [IndicadorController::class, 'updateWeights'])->name('weights.update');

            Route::get('/madre', [IndicadorController::class, 'mother'])->name('mother.index');
            Route::get('/madre/{indicator:code}', [IndicadorController::class, 'motherShow'])->name('mother.show');

            Route::get('/auditoria', [IndicadorController::class, 'auditLog'])->name('audit.index');

            Route::post('/dashboard/resumen', [IndicadorController::class, 'saveSummary'])->name('dashboard.summary');
        });
    });
