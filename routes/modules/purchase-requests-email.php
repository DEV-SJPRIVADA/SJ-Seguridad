<?php

use App\Http\Controllers\PurchaseRequests\PurchaseEmailApprovalController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchase-requests/aprobacion-correo')->name('purchase-requests.email-approval.')->group(function (): void {
    Route::middleware('signed')->group(function (): void {
        Route::get('/{purchase_request}', [PurchaseEmailApprovalController::class, 'show'])->name('show');
        Route::get('/{purchase_request}/pdf', [PurchaseEmailApprovalController::class, 'pdf'])->name('pdf');
        Route::post('/{purchase_request}', [PurchaseEmailApprovalController::class, 'update'])->name('update');
    });
});
