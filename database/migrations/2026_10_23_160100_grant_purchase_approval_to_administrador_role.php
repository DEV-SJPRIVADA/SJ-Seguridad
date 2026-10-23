<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * El rol administrador puede aprobar órdenes de compra (y solicitudes) en Autorizaciones compras.
     * Solo agrega el permiso; no quita ninguno.
     */
    public function up(): void
    {
        $role = Role::query()->where('name', 'administrador')->where('guard_name', 'web')->first();

        if ($role === null) {
            return;
        }

        $permission = Permission::findOrCreate('purchase.tab.approval', 'web');

        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::query()->where('name', 'administrador')->where('guard_name', 'web')->first();

        if ($role !== null && $role->hasPermissionTo('purchase.tab.approval')) {
            $role->revokePermissionTo('purchase.tab.approval');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
