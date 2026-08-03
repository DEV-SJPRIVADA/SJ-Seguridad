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
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $director = Role::query()->where('name', 'director')->where('guard_name', 'web')->first();

        if ($director) {
            $director->givePermissionTo([
                'view.board.gestion_humana.requisiciones',
                'view.board.compras.solicitudes_compra',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $director = Role::query()->where('name', 'director')->where('guard_name', 'web')->first();

        if ($director) {
            $director->revokePermissionTo([
                'view.board.gestion_humana.requisiciones',
                'view.board.compras.solicitudes_compra',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
