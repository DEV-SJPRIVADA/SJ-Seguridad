<?php

use App\Http\Controllers\PurchaseRequests\PurchaseEmailApprovalController;
use App\Http\Controllers\PurchaseRequests\PurchaseOrderApprovalController;
use App\Http\Controllers\PurchaseRequests\PurchaseOrderCancellationController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchase-requests/aprobacion-correo')->name('purchase-requests.email-approval.')->group(function (): void {
    Route::middleware('signed')->group(function (): void {
        Route::get('/{purchase_request}', [PurchaseEmailApprovalController::class, 'show'])->name('show');
        Route::get('/{purchase_request}/pdf', [PurchaseEmailApprovalController::class, 'pdf'])->name('pdf');
        Route::post('/{purchase_request}', [PurchaseEmailApprovalController::class, 'update'])->name('update');
    });
});

// Aprobación de OC por correo: enlace firmado al aprobador elegido por Compras (sin login).
Route::prefix('purchase-requests/aprobacion-oc-correo')->name('purchase-requests.order-approval-email.')->group(function (): void {
    Route::middleware('signed')->group(function (): void {
        Route::get('/{purchase_order}', [PurchaseOrderApprovalController::class, 'emailShow'])->name('show');
        Route::post('/{purchase_order}', [PurchaseOrderApprovalController::class, 'emailUpdate'])->name('update');
    });
});

// Anulación de OC por correo: enlace firmado al director elegido por Compras (sin login).
Route::prefix('purchase-requests/anulacion-oc-correo')->name('purchase-requests.order-cancellation-email.')->group(function (): void {
    Route::middleware('signed')->group(function (): void {
        Route::get('/{purchase_order}', [PurchaseOrderCancellationController::class, 'emailShow'])->name('show');
        Route::post('/{purchase_order}', [PurchaseOrderCancellationController::class, 'emailUpdate'])->name('update');
    });
});
