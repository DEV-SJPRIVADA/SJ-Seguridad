<?php

use App\Http\Controllers\Requisitions\RequisitionEmailApprovalController;
use Illuminate\Support\Facades\Route;

Route::prefix('requisitions/aprobacion-correo')->name('requisitions.email-approval.')->group(function (): void {
    Route::middleware('signed')->group(function (): void {
        Route::get('/{requisition}', [RequisitionEmailApprovalController::class, 'show'])->name('show');
        Route::post('/{requisition}', [RequisitionEmailApprovalController::class, 'update'])->name('update');
    });
});
