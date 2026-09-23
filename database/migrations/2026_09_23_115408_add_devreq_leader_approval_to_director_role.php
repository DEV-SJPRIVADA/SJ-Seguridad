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
            'devreq.tab.leader_approval',
            'view.board.tic.solicitudes_desarrollo',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $director = Role::query()->where('name', 'director')->where('guard_name', 'web')->first();

        if ($director) {
            $director->givePermissionTo([
                'devreq.tab.leader_approval',
                'view.board.tic.solicitudes_desarrollo',
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
                'devreq.tab.leader_approval',
                'view.board.tic.solicitudes_desarrollo',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
