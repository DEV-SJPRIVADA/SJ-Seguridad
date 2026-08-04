<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'view.board.gestion_humana.requisiciones',
            'view.board.compras.solicitudes_compra',
            'requisitions.approve.management',
            'purchase.tab.approval',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $director = Role::query()->where('name', 'director')->where('guard_name', 'web')->first();
        if ($director) {
            $director->revokePermissionTo([
                'requisitions.approve.management',
                'view.board.gestion_humana.requisiciones',
            ]);
            $director->syncPermissions([
                'view.dashboard',
                'purchase.tab.approval',
                'view.board.compras.solicitudes_compra',
            ]);
        }

        $administrador = Role::query()->where('name', 'administrador')->where('guard_name', 'web')->first();
        if ($administrador) {
            $administrador->syncPermissions([
                'view.dashboard',
                'manage.requisition.parameters',
                'requisitions.approve.management',
                'view.board.gestion_humana.requisiciones',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $director = Role::query()->where('name', 'director')->where('guard_name', 'web')->first();
        if ($director) {
            $director->syncPermissions([
                'view.dashboard',
                'purchase.tab.approval',
                'requisitions.approve.management',
                'view.board.gestion_humana.requisiciones',
                'view.board.compras.solicitudes_compra',
            ]);
        }

        $administrador = Role::query()->where('name', 'administrador')->where('guard_name', 'web')->first();
        if ($administrador) {
            $administrador->syncPermissions([
                'view.dashboard',
                'manage.requisition.parameters',
                'requisitions.approve.management',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
