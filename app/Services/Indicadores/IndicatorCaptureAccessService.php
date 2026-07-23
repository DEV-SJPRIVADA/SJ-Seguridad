<?php

namespace App\Services\Indicadores;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class IndicatorCaptureAccessService
{
    public const AREA_KEY = 'operaciones';

    /**
     * Usuarios activos asignados al area base Operaciones.
     *
     * @return Collection<int, User>
     */
    public function operacionesAreaUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('area_key', self::AREA_KEY)
            ->orderBy('name')
            ->get();
    }

    /**
     * Usuarios que pueden capturar o consolidar filas en indicadores.
     *
     * @return Collection<int, User>
     */
    public function capturableUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('area_key', self::AREA_KEY)
            ->permission(['operations.capture', 'operations.manage'])
            ->orderBy('name')
            ->get();
    }

    public function canCaptureIndicators(User $user): bool
    {
        if (! $user->is_active || $user->area_key !== self::AREA_KEY) {
            return false;
        }

        return $user->can('operations.capture') || $user->can('operations.manage');
    }

    public function isManageOnly(User $user): bool
    {
        return $user->can('operations.manage');
    }

    /**
     * @return list<string>
     */
    public function capturePermissionsToGrant(): array
    {
        $permissions = ['operations.capture', 'operations.view', 'view.board.'.self::AREA_KEY.'.indicadores'];

        if (! config('access.areas.'.self::AREA_KEY)) {
            return array_slice($permissions, 0, 2);
        }

        $permissions[] = 'view.area.'.self::AREA_KEY;

        return $permissions;
    }

    public function setCaptureEnabled(User $user, bool $enabled): void
    {
        if ($user->area_key !== self::AREA_KEY || ! $user->is_active) {
            throw new \InvalidArgumentException('El usuario no pertenece al area Operaciones activa.');
        }

        if ($this->isManageOnly($user)) {
            throw new \InvalidArgumentException('No se puede desactivar la captura de un administrador de indicadores.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($enabled) {
            $user->givePermissionTo($this->capturePermissionsToGrant());
        } else {
            $user->revokePermissionTo('operations.capture');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
