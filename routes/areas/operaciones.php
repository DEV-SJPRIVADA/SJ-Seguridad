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
        });

        Route::middleware(['indicador.tab:leaders'])->group(function (): void {
            Route::get('/jefes', [IndicadorController::class, 'leadersIndex'])->name('leaders.index');
            Route::get('/jefes/{operationsLeader}', [IndicadorController::class, 'leaderShow'])->name('leaders.show');
        });

        Route::middleware(['indicador.tab:leaders_manage'])->group(function (): void {
            Route::post('/jefes', [IndicadorController::class, 'leaderStore'])->name('leaders.store');
            Route::patch('/jefes/{operationsLeader}', [IndicadorController::class, 'leaderUpdate'])->name('leaders.update');
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

            Route::get('/documentos', [IndicadorController::class, 'documents'])->name('documents.index');
            Route::get('/documentos/crear', [IndicadorController::class, 'createDocument'])->name('documents.create');
            Route::post('/documentos', [IndicadorController::class, 'storeDocument'])->name('documents.store');
            Route::get('/documentos/{indicatorSystemDocument}', [IndicadorController::class, 'showDocument'])->name('documents.show');
            Route::get('/documentos/{indicatorSystemDocument}/editar', [IndicadorController::class, 'editDocument'])->name('documents.edit');
            Route::patch('/documentos/{indicatorSystemDocument}', [IndicadorController::class, 'updateDocument'])->name('documents.update');
            Route::delete('/documentos/{indicatorSystemDocument}', [IndicadorController::class, 'destroyDocument'])->name('documents.destroy');
            Route::post('/documentos/{indicatorSystemDocument}/versiones', [IndicadorController::class, 'storeDocumentVersion'])->name('documents.versions.store');

            Route::get('/madre', [IndicadorController::class, 'mother'])->name('mother.index');
            Route::get('/madre/{indicator:code}', [IndicadorController::class, 'motherShow'])->name('mother.show');

            Route::get('/auditoria', [IndicadorController::class, 'auditLog'])->name('audit.index');

            Route::post('/dashboard/resumen', [IndicadorController::class, 'saveSummary'])->name('dashboard.summary');
        });
    });
