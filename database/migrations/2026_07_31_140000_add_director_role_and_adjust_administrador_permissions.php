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
            'view.dashboard',
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'purchase.tab.approval',
            'purchase.tab.processing',
            'requisitions.approve.management',
            'manage.requisition.parameters',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $director = Role::findOrCreate('director', 'web');
        $director->syncPermissions([
            'view.dashboard',
            'purchase.tab.approval',
            'requisitions.approve.management',
        ]);

        $administrador = Role::findOrCreate('administrador', 'web');
        $administrador->syncPermissions([
            'view.dashboard',
            'manage.requisition.parameters',
            'requisitions.approve.management',
        ]);

        if (Permission::query()->where('name', 'manage.users')->where('guard_name', 'web')->exists()) {
            $administrador->revokePermissionTo('manage.users');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $administrador = Role::findByName('administrador', 'web');
        if ($administrador) {
            $administrador->givePermissionTo('manage.users');
        }

        Role::findByName('director', 'web')?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
