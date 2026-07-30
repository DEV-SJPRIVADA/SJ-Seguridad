<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const LEGACY_BOARD_PERMISSIONS = [
        'view.board.comercial.matriz_clientes',
        'view.board.comercial.servicios_comerciales',
    ];

    private const NEW_BOARD_PERMISSION = 'view.board.comercial.gestion_clientes';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(self::NEW_BOARD_PERMISSION, 'web');

        $newPermission = Permission::query()->where('name', self::NEW_BOARD_PERMISSION)->firstOrFail();

        foreach (self::LEGACY_BOARD_PERMISSIONS as $legacyName) {
            $legacyPermission = Permission::query()->where('name', $legacyName)->first();

            if ($legacyPermission === null) {
                continue;
            }

            $this->grantToUsersWithPermission($legacyPermission->id, $newPermission->id);
            $this->grantToRolesWithPermission($legacyPermission->id, $newPermission->id);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function grantToUsersWithPermission(int $legacyPermissionId, int $newPermissionId): void
    {
        $userIds = DB::table('model_has_permissions')
            ->where('permission_id', $legacyPermissionId)
            ->where('model_type', 'App\Models\User')
            ->pluck('model_id');

        foreach ($userIds as $userId) {
            $hasNew = DB::table('model_has_permissions')
                ->where('permission_id', $newPermissionId)
                ->where('model_type', 'App\Models\User')
                ->where('model_id', $userId)
                ->exists();

            if (! $hasNew) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $newPermissionId,
                    'model_type' => 'App\Models\User',
                    'model_id' => $userId,
                ]);
            }
        }
    }

    private function grantToRolesWithPermission(int $legacyPermissionId, int $newPermissionId): void
    {
        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $legacyPermissionId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $hasNew = DB::table('role_has_permissions')
                ->where('permission_id', $newPermissionId)
                ->where('role_id', $roleId)
                ->exists();

            if (! $hasNew) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $newPermissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Migracion de datos legacy; no reversible de forma segura.
    }
};
