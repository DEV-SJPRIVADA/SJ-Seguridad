<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const SOURCE_PERMISSION = 'cliente_interno.solicitudes.edit';

    /**
     * @var list<string>
     */
    private const TARGET_PERMISSIONS = [
        'cliente_interno.cartas_vacaciones.view',
        'cliente_interno.cartas_vacaciones.edit',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::TARGET_PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $sourcePermission = Permission::query()
            ->where('name', self::SOURCE_PERMISSION)
            ->where('guard_name', 'web')
            ->first();

        if ($sourcePermission === null) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        foreach (self::TARGET_PERMISSIONS as $targetName) {
            $targetPermission = Permission::query()
                ->where('name', $targetName)
                ->where('guard_name', 'web')
                ->firstOrFail();

            $this->grantToUsersWithPermission($sourcePermission->id, $targetPermission->id);
            $this->grantToRolesWithPermission($sourcePermission->id, $targetPermission->id);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function grantToUsersWithPermission(int $sourcePermissionId, int $targetPermissionId): void
    {
        $userIds = DB::table('model_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        foreach ($userIds as $userId) {
            $hasTarget = DB::table('model_has_permissions')
                ->where('permission_id', $targetPermissionId)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->exists();

            if (! $hasTarget) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $targetPermissionId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }
        }
    }

    private function grantToRolesWithPermission(int $sourcePermissionId, int $targetPermissionId): void
    {
        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $hasTarget = DB::table('role_has_permissions')
                ->where('permission_id', $targetPermissionId)
                ->where('role_id', $roleId)
                ->exists();

            if (! $hasTarget) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $targetPermissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Migración de datos; no reversible de forma segura.
    }
};
