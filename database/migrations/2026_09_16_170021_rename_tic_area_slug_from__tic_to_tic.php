<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Normaliza el slug de area Tic -> tic (permisos Spatie + area_key en tablas).
 *
 * Nota AgentSj: renombra view.area.Tic / manage.area.Tic / view.board.Tic.*
 * a view.area.tic / manage.area.tic / view.board.tic.* conservando asignaciones.
 */
return new class extends Migration
{
    private const OLD_KEY = 'Tic';

    private const NEW_KEY = 'tic';

    public function up(): void
    {
        $this->migrateAreaKeys();
        $this->migratePermissions();
    }

    public function down(): void
    {
        $this->revertAreaKeys();
        $this->revertPermissions();
    }

    private function migrateAreaKeys(): void
    {
        $this->updateAreaKeyColumns(self::OLD_KEY, self::NEW_KEY);
    }

    private function revertAreaKeys(): void
    {
        $this->updateAreaKeyColumns(self::NEW_KEY, self::OLD_KEY);
    }

    private function updateAreaKeyColumns(string $from, string $to): void
    {
        $columnsByTable = [
            'users' => ['area_key'],
            'personal_requisitions' => ['requesting_area_key', 'operating_area_key'],
            'supply_requests' => ['area_key'],
            'purchase_requests' => ['area_key'],
            'quality_document_areas' => ['area_key'],
            'user_managed_areas' => ['area_key'],
        ];

        foreach ($columnsByTable as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->where($column, $from)
                    ->update([$column => $to]);
            }
        }

        if (! Schema::hasTable('quality_document_areas') || ! Schema::hasColumn('quality_document_areas', 'area_key')) {
            return;
        }

        $duplicateGroups = DB::table('quality_document_areas')
            ->select('quality_document_id', 'area_key', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->where('area_key', $to)
            ->groupBy('quality_document_id', 'area_key')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('quality_document_areas')
                ->where('quality_document_id', $group->quality_document_id)
                ->where('area_key', $to)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }
    }

    private function migratePermissions(): void
    {
        $this->renamePermissionSet(self::OLD_KEY, self::NEW_KEY);
    }

    private function revertPermissions(): void
    {
        $this->renamePermissionSet(self::NEW_KEY, self::OLD_KEY);
    }

    private function renamePermissionSet(string $fromKey, string $toKey): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $boardKeys = collect(config('access.boards', []))
            ->keys()
            ->reject(fn (string $boardKey) => $boardKey === 'documentos')
            ->values()
            ->all();

        foreach (['view.area', 'manage.area'] as $prefix) {
            $this->mergePermission("{$prefix}.{$fromKey}", "{$prefix}.{$toKey}");
        }

        foreach ($boardKeys as $boardKey) {
            $this->mergePermission(
                "view.board.{$fromKey}.{$boardKey}",
                "view.board.{$toKey}.{$boardKey}"
            );
        }

        // Permisos residuales view.board.Tic.* que no esten en boards actuales.
        $legacyBoards = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', "view.board.{$fromKey}.%")
            ->pluck('name');

        foreach ($legacyBoards as $oldName) {
            $suffix = substr((string) $oldName, strlen("view.board.{$fromKey}."));
            $this->mergePermission($oldName, "view.board.{$toKey}.{$suffix}");
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

        // MySQL unique a menudo es case-insensitive: Tic/tic colisionan.
        if (strcasecmp($oldName, $newName) === 0) {
            if ($oldPermission->name !== $newName) {
                $oldPermission->name = $newName;
                $oldPermission->save();
            }

            return;
        }

        $newPermission = Permission::query()
            ->where('name', $newName)
            ->where('guard_name', 'web')
            ->first();

        if ($newPermission === null) {
            $newPermission = Permission::query()
                ->where('guard_name', 'web')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($newName)])
                ->first();
        }

        if ($newPermission === null) {
            $newPermission = Permission::create([
                'name' => $newName,
                'guard_name' => 'web',
            ]);
        } elseif ($newPermission->name !== $newName) {
            $newPermission->name = $newName;
            $newPermission->save();
        }

        if ((int) $oldPermission->id === (int) $newPermission->id) {
            return;
        }

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
