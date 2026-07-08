<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionCatalog
{
    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function configuredNames(): Collection
    {
        return collect(config('access.system_permissions'))
            ->keys()
            ->merge(
                collect(config('access.areas'))->flatMap(fn (string $label, string $key) => [
                    "view.area.{$key}",
                    "manage.area.{$key}",
                ])
            )
            ->merge(
                collect(config('access.areas'))->keys()->flatMap(fn (string $areaKey) => (
                    collect(config('access.boards'))
                        ->keys()
                        ->reject(fn (string $boardKey) => $boardKey === 'documentos')
                        ->map(fn (string $boardKey) => "view.board.{$areaKey}.{$boardKey}")
                ))
            )
            ->unique()
            ->values();
    }

    /**
     * @return array{synced: int, deleted: int}
     */
    public static function sync(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = static::configuredNames();

        $permissions->each(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        $orphans = Permission::query()
            ->where(function ($query): void {
                $query->where('name', 'like', 'view.area.%')
                    ->orWhere('name', 'like', 'manage.area.%')
                    ->orWhere('name', 'like', 'view.board.%');
            })
            ->whereNotIn('name', $permissions->all())
            ->get();

        $deletedCount = $orphans->count();
        $orphans->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'synced' => $permissions->count(),
            'deleted' => $deletedCount,
        ];
    }
}
