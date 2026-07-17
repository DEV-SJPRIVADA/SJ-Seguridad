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
                collect(config('access.area_indicador_permissions', []))
                    ->flatMap(fn (array $permissions) => array_keys($permissions))
            )
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
                        ->reject(function (string $boardKey) use ($areaKey): bool {
                            if ($boardKey === 'documentos') {
                                return true;
                            }

                            if ($boardKey === 'indicadores' && $areaKey !== 'operaciones') {
                                return true;
                            }

                            if ($boardKey === 'matriz_clientes' && $areaKey !== 'comercial') {
                                return true;
                            }

                            if ($boardKey === 'servicios_comerciales' && $areaKey !== 'comercial') {
                                return true;
                            }

                            return false;
                        })
                        ->map(fn (string $boardKey) => "view.board.{$areaKey}.{$boardKey}")
                ))
            )
            ->unique()
            ->values();
    }

    /**
     * Garantiza que los permisos funcionales de system_permissions existan en Spatie.
     * Usado al cargar Admin de usuarios para que el formulario refleje config/access.php.
     */
    public static function ensureSystemPermissions(): void
    {
        collect(config('access.system_permissions', []))
            ->keys()
            ->each(fn (string $permission) => Permission::findOrCreate($permission, 'web'));
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
