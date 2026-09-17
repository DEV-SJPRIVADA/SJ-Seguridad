<?php

use App\Http\Controllers\DevelopmentRequests\DevelopmentRequestController;
use App\Http\Controllers\DevelopmentRequests\DevelopmentRequestLeaderController;
use App\Http\Controllers\DevelopmentRequests\DevelopmentRequestTicController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])
    ->prefix('development-requests/{module}')
    ->name('development-requests.')
    ->group(function (): void {
        Route::get('/', [DevelopmentRequestController::class, 'index'])->name('index');

        Route::middleware(['devreq.tab:create'])->group(function (): void {
            Route::get('/nueva', [DevelopmentRequestController::class, 'create'])->name('create');
            Route::post('/nueva', [DevelopmentRequestController::class, 'store'])->name('store');
        });

        Route::middleware(['devreq.tab:my_requests'])->group(function (): void {
            Route::get('/mis-solicitudes', [DevelopmentRequestController::class, 'myRequests'])->name('my-requests');
            Route::get('/mis-solicitudes/{development_request}/editar', [DevelopmentRequestController::class, 'edit'])->name('edit');
            Route::patch('/mis-solicitudes/{development_request}', [DevelopmentRequestController::class, 'update'])->name('update');
        });

        Route::middleware(['devreq.tab:leader_approval'])->group(function (): void {
            Route::get('/aprobacion-lider', [DevelopmentRequestLeaderController::class, 'index'])->name('leader-approval');
            Route::patch('/solicitud/{development_request}/lider', [DevelopmentRequestLeaderController::class, 'update'])->name('leader.update');
        });

        Route::middleware(['devreq.tab:tic_queue'])->group(function (): void {
            Route::get('/bandeja-tic', [DevelopmentRequestTicController::class, 'index'])->name('tic-queue');
            Route::get('/bandeja-tic/exportar', [DevelopmentRequestTicController::class, 'export'])->name('tic-queue.export');
            Route::patch('/solicitud/{development_request}/tic', [DevelopmentRequestTicController::class, 'transition'])->name('tic.transition');
        });

        Route::post('/solicitud/{development_request}/mensajes', [DevelopmentRequestTicController::class, 'storeMessage'])
            ->name('messages.store');
        Route::patch('/solicitud/{development_request}/uat', [DevelopmentRequestTicController::class, 'uat'])
            ->name('uat.update');

        Route::get('/solicitud/{development_request}/adjuntos/{attachment}', [DevelopmentRequestController::class, 'downloadAttachment'])
            ->scopeBindings()
            ->name('attachments.download');
        Route::get('/solicitud/{development_request}', [DevelopmentRequestController::class, 'show'])->name('show');
    });
