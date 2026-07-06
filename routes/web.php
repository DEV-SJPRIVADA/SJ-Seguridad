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
            return redirect(auth()->user()->defaultRequisitionBoardUrl($defaultModule['key']));
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
        return redirect(auth()->user()->defaultRequisitionBoardUrl($selectedModule['key']));
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

    // Modulos del sistema
    require __DIR__.'/modules/requisitions.php';
    require __DIR__.'/modules/supplies.php';
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
