<?php

use App\Http\Controllers\Matrices\MatrixAttachmentController;
use App\Http\Controllers\Matrices\MatrixBuilderController;
use App\Http\Controllers\Matrices\MatrixController;
use App\Http\Controllers\Matrices\MatrixExportController;
use App\Http\Controllers\Matrices\MatrixShareController;
use App\Http\Controllers\Matrices\MatrixSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('matrices/{module}')->name('matrices.')->group(function (): void {
    Route::get('/', [MatrixController::class, 'index'])->name('index');

    Route::get('/crear', [MatrixBuilderController::class, 'create'])->name('create');
    Route::post('/', [MatrixBuilderController::class, 'store'])->name('store');

    Route::get('/{matrix}/editar', [MatrixBuilderController::class, 'edit'])->name('edit');
    Route::put('/{matrix}', [MatrixBuilderController::class, 'update'])->name('update');
    Route::post('/{matrix}/publicar', [MatrixBuilderController::class, 'publish'])->name('publish');
    Route::post('/{matrix}/archivar', [MatrixController::class, 'archive'])->name('archive');

    Route::get('/{matrix}/compartir', [MatrixShareController::class, 'index'])->name('share.index');
    Route::post('/{matrix}/compartir', [MatrixShareController::class, 'store'])->name('share.store');
    Route::delete('/{matrix}/compartir/usuario/{userAccess}', [MatrixShareController::class, 'destroyUser'])->name('share.destroy-user');
    Route::delete('/{matrix}/compartir/area/{areaAccess}', [MatrixShareController::class, 'destroyArea'])->name('share.destroy-area');

    Route::get('/{matrix}/registros', [MatrixSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/{matrix}/registros/datatable', [MatrixSubmissionController::class, 'datatable'])->name('submissions.datatable');
    Route::get('/{matrix}/registros/crear', [MatrixSubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/{matrix}/registros', [MatrixSubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/{matrix}/registros/{submission}', [MatrixSubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/{matrix}/registros/{submission}/editar', [MatrixSubmissionController::class, 'edit'])->name('submissions.edit');
    Route::put('/{matrix}/registros/{submission}', [MatrixSubmissionController::class, 'update'])->name('submissions.update');
    Route::post('/{matrix}/registros/{submission}/archivar', [MatrixSubmissionController::class, 'archive'])->name('submissions.archive');

    Route::get('/{matrix}/exportar', [MatrixExportController::class, 'export'])->name('export');

    Route::get('/{matrix}/adjuntos/{attachment}/descargar', [MatrixAttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/{matrix}/adjuntos/{attachment}/ver', [MatrixAttachmentController::class, 'preview'])->name('attachments.preview');
    Route::delete('/{matrix}/adjuntos/{attachment}', [MatrixAttachmentController::class, 'destroy'])->name('attachments.destroy');
});
