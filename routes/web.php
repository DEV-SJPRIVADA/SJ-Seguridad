<?php

use App\Http\Controllers\Admin\NotificationConfigController;
use App\Http\Controllers\Admin\SupplySiteController;
use App\Http\Controllers\Admin\SystemAuditController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Mail\PersonalRequisitionNotification;
use App\Models\PersonalRequisition;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $areas = collect(config('access.areas'))
        ->map(function (string $label, string $key) {
            $user = auth()->user();
            $boards = collect(config('access.boards', []))
                ->map(function (string $boardLabel, string $boardKey) use ($key, $user) {
                    if ($boardKey === 'documentos') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $user->canViewDocumentsBoardFor($key),
                        ];
                    }

                    if ($boardKey === 'indicadores') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $key === 'operaciones' && (
                                $user->can('operations.view')
                                || $user->can('operations.manage')
                                || $user->can('operations.capture')
                            ),
                        ];
                    }

                    if ($boardKey === 'gestion_clientes') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $key === 'comercial' && (
                                $user->can('view.board.comercial.gestion_clientes')
                                || $user->can('comercial.matriz.view')
                                || $user->can('comercial.matriz.manage')
                                || $user->can('view.board.comercial.matriz_clientes')
                                || $user->can('view.board.comercial.servicios_comerciales')
                            ),
                        ];
                    }

                    if ($boardKey === 'requisiciones') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $user->canViewRequisitionsBoardFor($key),
                        ];
                    }

                    if ($boardKey === 'suministros') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $user->canViewSupplyBoardFor($key),
                        ];
                    }

                    if ($boardKey === 'solicitudes_compra') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $user->canViewPurchaseBoardFor($key),
                        ];
                    }

                    if ($boardKey === 'bandeja_compras') {
                        return [
                            'key' => $boardKey,
                            'label' => $boardLabel,
                            'can_view' => $user->can('purchase.tab.processing'),
                        ];
                    }

                    return [
                        'key' => $boardKey,
                        'label' => $boardLabel,
                        'can_view' => $user->can("view.board.{$key}.{$boardKey}"),
                    ];
                })
                ->filter(fn (array $board) => $board['can_view'])
                ->values();

            return [
                'key' => $key,
                'label' => $label,
                'can_manage' => $user->can("manage.area.{$key}"),
                'can_view' => $boards->isNotEmpty(),
                'boards' => $boards,
            ];
        })
        ->filter(fn (array $area) => $area['boards']->isNotEmpty())
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

        if ($defaultBoard === 'solicitudes_compra') {
            return redirect(auth()->user()->defaultPurchaseBoardUrl($defaultModule['key']));
        }

        if ($defaultBoard === 'bandeja_compras') {
            return redirect()->route('purchase-requests.processing.index', ['module' => $defaultModule['key']]);
        }

        if ($defaultBoard === 'documentos') {
            return redirect(auth()->user()->defaultQualityDocumentBoardUrl($defaultModule['key']));
        }

        if ($defaultBoard === 'indicadores') {
            return redirect(auth()->user()->defaultIndicadorBoardUrl());
        }

        if ($defaultBoard === 'gestion_clientes') {
            return redirect(auth()->user()->defaultGestionClientesBoardUrl());
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

    if ($selectedModule && $selectedBoardKey === 'solicitudes_compra') {
        return redirect(auth()->user()->defaultPurchaseBoardUrl($selectedModule['key']));
    }

    if ($selectedModule && $selectedBoardKey === 'bandeja_compras') {
        return redirect()->route('purchase-requests.processing.index', ['module' => $selectedModule['key']]);
    }

    if ($selectedModule && $selectedBoardKey === 'documentos') {
        return redirect(auth()->user()->defaultQualityDocumentBoardUrl($selectedModule['key']));
    }

    if ($selectedModule && $selectedBoardKey === 'indicadores') {
        return redirect(auth()->user()->defaultIndicadorBoardUrl());
    }

    if ($selectedModuleKey === 'comercial' && $selectedBoardKey === 'dashboard') {
        return redirect()->route('comercial.dashboard');
    }

    if ($selectedModuleKey === 'compras' && $selectedBoardKey === 'dashboard') {
        return redirect()->route('compras.dashboard');
    }

    if ($selectedModule && $selectedBoardKey === 'gestion_clientes') {
        return redirect(auth()->user()->defaultGestionClientesBoardUrl());
    }

    if ($selectedModule && $selectedBoardKey === 'matriz_clientes') {
        return redirect()->route('comercial.matriz.clients.index');
    }

    if ($selectedModule && $selectedBoardKey === 'servicios_comerciales') {
        return redirect()->route('comercial.matriz.services.index');
    }

    return view('dashboard', [
        'areas' => $areas,
        'selectedBoard' => $selectedBoard,
        'selectedModule' => $selectedModule,
    ]);
})->middleware(['auth', 'active', 'password.changed', 'can:view.dashboard'])->name('dashboard');

require __DIR__.'/modules/purchase-requests-email.php';
require __DIR__.'/modules/requisitions-email.php';

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['password.changed', 'permission:manage.users'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('supply-sites', [SupplySiteController::class, 'index'])->name('supply-sites.index');
        Route::post('supply-sites', [SupplySiteController::class, 'store'])->name('supply-sites.store');
        Route::patch('supply-sites/{supply_site}', [SupplySiteController::class, 'update'])->name('supply-sites.update');
        Route::delete('supply-sites/{supply_site}', [SupplySiteController::class, 'destroy'])->name('supply-sites.destroy');

        Route::post('users/{user}/apply-access', [UserController::class, 'applyAccess'])->name('users.apply-access');
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });

    Route::middleware(['password.changed', 'permission:manage.notifications'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('notificaciones', [NotificationConfigController::class, 'index'])->name('notifications.index');
        Route::post('notificaciones/tipos/{notification_type}/correos', [NotificationConfigController::class, 'attachTypeEmail'])->name('notifications.types.emails.attach');
        Route::delete('notificaciones/tipos/{notification_type}/correos/{notification_email}', [NotificationConfigController::class, 'detachTypeEmail'])->name('notifications.types.emails.detach');
    });

    Route::middleware(['password.changed', 'permission:system.view.audit'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('auditoria', [SystemAuditController::class, 'index'])->name('audit.index');
    });

    // Modulos del sistema
    require __DIR__.'/modules/requisitions.php';
    require __DIR__.'/modules/supplies.php';
    require __DIR__.'/modules/purchase-requests.php';
    require __DIR__.'/modules/quality-documents.php';
    require __DIR__.'/areas/operaciones.php';
    require __DIR__.'/areas/comercial.php';
    require __DIR__.'/areas/gestion_humana.php';
    require __DIR__.'/areas/compras.php';
});

if (app()->environment('local')) {
    Route::get('/mail-preview', function () {
        $requisition = PersonalRequisition::with(['position', 'client', 'requester'])->latest()->first();

        if (! $requisition) {
            return 'No hay requisiciones creadas para visualizar el correo.';
        }

        return new PersonalRequisitionNotification($requisition, 3);
    })->middleware(['auth', 'permission:manage.users']);
}

require __DIR__.'/auth.php';
