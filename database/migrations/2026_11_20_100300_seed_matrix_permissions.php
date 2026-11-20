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

        Permission::findOrCreate('matrices.manage', 'web');

        Role::findOrCreate('super-admin', 'web')->givePermissionTo('matrices.manage');

        $administrador = Role::query()
            ->where('name', 'administrador')
            ->where('guard_name', 'web')
            ->first();

        $administrador?->givePermissionTo('matrices.manage');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()
            ->where('name', 'administrador')
            ->where('guard_name', 'web')
            ->first()
            ?->revokePermissionTo('matrices.manage');

        Permission::query()
            ->where('name', 'matrices.manage')
            ->where('guard_name', 'web')
            ->first()
            ?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
