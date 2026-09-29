<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private const PERMISSION_MAP = [
        'comercial.matriz.view' => [
            'comercial.clients.view',
            'comercial.services.view',
            'view.board.comercial.dashboard',
        ],
        'comercial.matriz.manage' => [
            'comercial.clients.edit',
            'comercial.services.edit',
            'comercial.parameters.edit',
            'view.board.comercial.dashboard',
        ],
        'view.board.comercial.matriz_clientes' => [
            'comercial.clients.view',
        ],
        'view.board.comercial.servicios_comerciales' => [
            'comercial.services.view',
        ],
        'manage.commercial.parameters' => [
            'comercial.parameters.edit',
        ],
    ];

    /**
     * @var list<string>
     */
    private const NEW_PERMISSIONS = [
        'comercial.clients.view',
        'comercial.clients.edit',
        'comercial.services.view',
        'comercial.services.edit',
        'comercial.parameters.edit',
        'view.board.comercial.dashboard',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::NEW_PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (self::PERMISSION_MAP as $legacyName => $newNames) {
            $legacyPermission = Permission::query()->where('name', $legacyName)->first();

            if ($legacyPermission === null) {
                continue;
            }

            foreach ($newNames as $newName) {
                $newPermission = Permission::query()->where('name', $newName)->firstOrFail();
                $this->grantToUsersWithPermission($legacyPermission->id, $newPermission->id);
                $this->grantToRolesWithPermission($legacyPermission->id, $newPermission->id);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function grantToUsersWithPermission(int $legacyPermissionId, int $newPermissionId): void
    {
        $userIds = DB::table('model_has_permissions')
            ->where('permission_id', $legacyPermissionId)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        foreach ($userIds as $userId) {
            $hasNew = DB::table('model_has_permissions')
                ->where('permission_id', $newPermissionId)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->exists();

            if (! $hasNew) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $newPermissionId,
                    'model_type' => 'App\\Models\\User',
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
        // Migracion de datos; no reversible de forma segura.
    }
};
