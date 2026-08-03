<?php

use App\Http\Controllers\Compras\ComprasDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['password.changed'])->group(function (): void {
    Route::get('compras/dashboard', ComprasDashboardController::class)->name('compras.dashboard');
});
