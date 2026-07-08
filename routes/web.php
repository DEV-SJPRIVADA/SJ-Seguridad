<?php

use App\Http\Controllers\Admin\SupplySiteController;
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
            $user = auth()->user();
            $canView = $user->can("view.area.{$key}") || $user->can("manage.area.{$key}");
            $canManage = $user->can("manage.area.{$key}");
            $boards = collect(config('access.boards', []))
                ->map(function (string $boardLabel, string $boardKey) use ($key, $user, $canView) {
                    if ($boardKey === 'documentos') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $user->canViewDocumentsBoardFor($key),
                        ];
                    }

                    return [
                        'key' => $boardKey,
                        'label' => $boardLabel,
                        'can_view' => $boardKey === 'dashboard'
                            ? ($canView || $user->can("view.board.{$key}.{$boardKey}"))
                            : $user->can("view.board.{$key}.{$boardKey}"),
                    ];
                })
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

        if ($defaultBoard === 'suministros') {
            return redirect(auth()->user()->defaultSupplyBoardUrl($defaultModule['key']));
        }

        if ($defaultBoard === 'documentos') {
            return redirect(auth()->user()->defaultQualityDocumentBoardUrl($defaultModule['key']));
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

    if ($selectedModule && $selectedBoardKey === 'suministros') {
        return redirect(auth()->user()->defaultSupplyBoardUrl($selectedModule['key']));
    }

    if ($selectedModule && $selectedBoardKey === 'documentos') {
        return redirect(auth()->user()->defaultQualityDocumentBoardUrl($selectedModule['key']));
    }

    return view('dashboard', [
        'areas' => $areas,
        'selectedBoard' => $selectedBoard,
        'selectedModule' => $selectedModule,
    ]);
})->middleware(['auth', 'active', 'password.changed', 'can:view.dashboard'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['password.changed', 'permission:manage.users'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('supply-sites', [SupplySiteController::class, 'index'])->name('supply-sites.index');
        Route::post('supply-sites', [SupplySiteController::class, 'store'])->name('supply-sites.store');
        Route::patch('supply-sites/{supply_site}', [SupplySiteController::class, 'update'])->name('supply-sites.update');
        Route::delete('supply-sites/{supply_site}', [SupplySiteController::class, 'destroy'])->name('supply-sites.destroy');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });

    // Modulos del sistema
    require __DIR__.'/modules/requisitions.php';
    require __DIR__.'/modules/supplies.php';
    require __DIR__.'/modules/quality-documents.php';
});

if (app()->environment('local')) {
    Route::get('/mail-preview', function () {
        $requisition = \App\Models\PersonalRequisition::with(['position', 'client', 'requester'])->latest()->first();

        if (! $requisition) {
            return 'No hay requisiciones creadas para visualizar el correo.';
        }

        return new \App\Mail\PersonalRequisitionNotification($requisition, 3);
    })->middleware(['auth', 'permission:manage.users']);
}

require __DIR__.'/auth.php';
