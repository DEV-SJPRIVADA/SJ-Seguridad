<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequisitionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $areas = collect(config('access.areas'))
        ->map(function (string $label, string $key) {
            $canView = auth()->user()->can("view.area.{$key}") || auth()->user()->can("manage.area.{$key}");
            $canManage = auth()->user()->can("manage.area.{$key}");
            $boards = collect(config('access.boards', []))
                ->map(fn (string $boardLabel, string $boardKey) => [
                    'key' => $boardKey,
                    'label' => $boardLabel,
                    'can_view' => $boardKey === 'dashboard'
                        ? ($canView || auth()->user()->can("view.board.{$key}.{$boardKey}"))
                        : auth()->user()->can("view.board.{$key}.{$boardKey}"),
                ])
                ->filter(fn (array $board) => $board['can_view'])
                ->values();

            return [
                'key' => $key,
                'label' => $label,
                'can_manage' => $canManage,
                'can_view' => $canView,
                'boards' => $boards,
            ];
        })
        ->filter(fn (array $area) => $area['can_view'] || $area['boards']->isNotEmpty())
        ->values();

    $selectedModuleKey = request()->string('module')->toString();

    if ($selectedModuleKey === '' && $areas->isNotEmpty()) {
        $defaultModule = $areas->first();
        $defaultBoard = $defaultModule['boards']->first()['key'] ?? null;

        if ($defaultBoard === 'requisiciones') {
            return redirect()->route('requisitions.dashboard', ['module' => $defaultModule['key']]);
        }

        return redirect()->route('dashboard', array_filter([
            'module' => $defaultModule['key'],
            'board' => $defaultBoard,
        ]));
    }

    $selectedModule = $areas->firstWhere('key', $selectedModuleKey);
    $selectedBoardKey = request()->string('board')->toString();
    $selectedBoard = $selectedModule
        ? $selectedModule['boards']->firstWhere('key', $selectedBoardKey)
        : null;

    if ($selectedModule && $selectedBoardKey === 'requisiciones') {
        return redirect()->route('requisitions.dashboard', ['module' => $selectedModule['key']]);
    }

    return view('dashboard', [
        'areas' => $areas,
        'selectedBoard' => $selectedBoard,
        'selectedModule' => $selectedModule,
    ]);
})->middleware(['auth', 'active', 'password.changed'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['password.changed', 'permission:manage.users'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });

    Route::middleware(['password.changed'])->prefix('requisitions/{module}')->name('requisitions.')->group(function () {
        Route::get('/dashboard', [RequisitionController::class, 'dashboard'])->name('dashboard');
        Route::get('/solicitar', [RequisitionController::class, 'create'])->name('create');
        Route::post('/solicitar', [RequisitionController::class, 'store'])->name('store');
        Route::get('/gestion', [RequisitionController::class, 'manage'])->name('manage');
        Route::get('/gestion/{requisition}/editar', [RequisitionController::class, 'edit'])->name('edit');
        Route::get('/gestion/{requisition}/imprimir', [RequisitionController::class, 'print'])->name('print');
        Route::patch('/gestion/{requisition}', [RequisitionController::class, 'update'])->name('update');
        Route::get('/parametros', [RequisitionController::class, 'parameters'])->name('parameters');
        Route::post('/parametros/{type}', [RequisitionController::class, 'storeParameter'])->name('parameters.store');
        Route::patch('/parametros/{type}/{parameterId}', [RequisitionController::class, 'updateParameter'])->name('parameters.update');
        Route::delete('/parametros/{type}/{parameterId}', [RequisitionController::class, 'destroyParameter'])->name('parameters.destroy');
    });
});

Route::get('/mail-preview', function () {
    $requisition = \App\Models\PersonalRequisition::with(['position', 'client', 'requester'])->latest()->first();
    
    if (!$requisition) {
        return "No hay requisiciones creadas para visualizar el correo.";
    }

    // Le pasamos la requisición y simulamos que fue un lote de 3 vacantes
    return new \App\Mail\PersonalRequisitionNotification($requisition, 3);
})->middleware(['auth']);

require __DIR__.'/auth.php';
