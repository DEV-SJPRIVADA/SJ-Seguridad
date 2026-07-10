<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const OLD_KEYS = ['remuneraciones', 'facturacion'];

    private const NEW_KEY = 'admin_financiero';

    public function up(): void
    {
        $this->migrateAreaKeys();
        $this->migratePermissions();
    }

    public function down(): void
    {
        // La unificacion de dos areas no es reversible de forma segura sin perder contexto.
    }

    private function migrateAreaKeys(): void
    {
        $columnsByTable = [
            'users' => ['area_key'],
            'personal_requisitions' => ['requesting_area_key', 'operating_area_key'],
            'supply_requests' => ['area_key'],
            'quality_document_areas' => ['area_key'],
        ];

        foreach ($columnsByTable as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->whereIn($column, self::OLD_KEYS)
                    ->update([$column => self::NEW_KEY]);
            }
        }

        $duplicateGroups = DB::table('quality_document_areas')
            ->select('quality_document_id', 'area_key', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->where('area_key', self::NEW_KEY)
            ->groupBy('quality_document_id', 'area_key')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('quality_document_areas')
                ->where('quality_document_id', $group->quality_document_id)
                ->where('area_key', self::NEW_KEY)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }
    }

    private function migratePermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $boardKeys = collect(config('access.boards', []))
            ->keys()
            ->reject(fn (string $boardKey) => $boardKey === 'documentos')
            ->values()
            ->all();

        foreach (self::OLD_KEYS as $oldKey) {
            foreach (['view.area', 'manage.area'] as $prefix) {
                $this->mergePermission("{$prefix}.{$oldKey}", "{$prefix}.".self::NEW_KEY);
            }

            foreach ($boardKeys as $boardKey) {
                $this->mergePermission(
                    "view.board.{$oldKey}.{$boardKey}",
                    'view.board.'.self::NEW_KEY.".{$boardKey}"
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function mergePermission(string $oldName, string $newName): void
    {
        $oldPermission = Permission::query()
            ->where('name', $oldName)
            ->where('guard_name', 'web')
            ->first();

        if (! $oldPermission) {
            return;
        }

        $newPermission = Permission::findOrCreate($newName, 'web');

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $newPermission->id,
                'role_id' => $roleId,
            ]);
        }

        DB::table('role_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->delete();

        $modelAssignments = DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->get(['model_id', 'model_type']);

        foreach ($modelAssignments as $assignment) {
            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $newPermission->id,
                'model_type' => $assignment->model_type,
                'model_id' => $assignment->model_id,
            ]);
        }

        DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->delete();

        $oldPermission->delete();
    }
};
