<?php

use App\Http\Controllers\QualityDocuments\QualityDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])->prefix('quality-documents/{module}')->name('quality-documents.')->group(function () {
    Route::get('/biblioteca', [QualityDocumentController::class, 'libraryIndex'])->name('library.index');
    Route::get('/biblioteca/{qualityDocument}/descargar', [QualityDocumentController::class, 'download'])->name('library.download');
    Route::get('/biblioteca/{qualityDocument}/abrir', [QualityDocumentController::class, 'openLink'])->name('library.open');

    Route::get('/mis-documentos', [QualityDocumentController::class, 'myDocuments'])->name('mine.index');
    Route::get('/mis-documentos/{qualityDocument}/descargar', [QualityDocumentController::class, 'downloadMine'])->name('mine.download');
    Route::get('/mis-documentos/{qualityDocument}/abrir', [QualityDocumentController::class, 'openMine'])->name('mine.open');

    Route::middleware(['can:manage.quality.documents'])->prefix('administrar')->name('admin.')->group(function () {
        Route::get('/', [QualityDocumentController::class, 'adminIndex'])->name('index');
        Route::get('/crear', [QualityDocumentController::class, 'create'])->name('create');
        Route::post('/', [QualityDocumentController::class, 'store'])->name('store');
        Route::get('/{qualityDocument}/editar', [QualityDocumentController::class, 'edit'])->name('edit');
        Route::patch('/{qualityDocument}', [QualityDocumentController::class, 'update'])->name('update');
        Route::patch('/{qualityDocument}/estado', [QualityDocumentController::class, 'toggleStatus'])->name('toggle');
        Route::delete('/{qualityDocument}', [QualityDocumentController::class, 'destroy'])->name('destroy');
    });
});
