<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
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
                    'can_view' => auth()->user()->can("view.board.{$key}.{$boardKey}"),
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
    $selectedModule = $areas->firstWhere('key', $selectedModuleKey);
    $selectedBoardKey = request()->string('board')->toString();
    $selectedBoard = $selectedModule
        ? $selectedModule['boards']->firstWhere('key', $selectedBoardKey)
        : null;

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
});

require __DIR__.'/auth.php';
