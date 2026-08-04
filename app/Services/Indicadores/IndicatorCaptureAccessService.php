<?php

namespace App\Services\Indicadores;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
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

    public function canDelegateCapture(User $user): bool
    {
        if (! $user->is_active || $user->area_key !== self::AREA_KEY) {
            return false;
        }

        return $user->can('operations.capture.delegate');
    }

    public function canAccessCaptureScreen(User $user): bool
    {
        return $this->canCaptureIndicators($user) || $this->canDelegateCapture($user);
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
            $this->ensurePermissionsExist($this->capturePermissionsToGrant());
            $user->givePermissionTo($this->capturePermissionsToGrant());
        } else {
            $user->revokePermissionTo('operations.capture');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    public function delegatePermissionsToGrant(): array
    {
        $permissions = ['operations.capture.delegate', 'operations.view', 'view.board.'.self::AREA_KEY.'.indicadores'];

        if (! config('access.areas.'.self::AREA_KEY)) {
            return array_slice($permissions, 0, 2);
        }

        $permissions[] = 'view.area.'.self::AREA_KEY;

        return $permissions;
    }

    public function setDelegateCaptureEnabled(User $user, bool $enabled): void
    {
        if ($user->area_key !== self::AREA_KEY || ! $user->is_active) {
            throw new \InvalidArgumentException('El usuario no pertenece al area Operaciones activa.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($enabled) {
            $this->ensurePermissionsExist($this->delegatePermissionsToGrant());
            $user->givePermissionTo($this->delegatePermissionsToGrant());
        } else {
            $user->revokePermissionTo('operations.capture.delegate');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function ensurePermissionsExist(array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    /**
     * Resuelve el usuario titular (capturador) para la pantalla o el guardado de captura.
     *
     * Reglas ver docs/briefs/FEAT-023.md — seccion "Decisiones tecnicas - resolucion de titular".
     */
    public function resolveTitularUser(User $actor, ?int $capturadorId): User
    {
        $capturableUsers = $this->capturableUsers();

        if ($this->canCaptureIndicators($actor) && ! $this->canDelegateCapture($actor)) {
            return $actor;
        }

        if ($capturadorId !== null) {
            $titular = $capturableUsers->firstWhere('id', $capturadorId);
            abort_unless($titular !== null, 404);

            return $titular;
        }

        if ($this->canCaptureIndicators($actor)) {
            $self = $capturableUsers->firstWhere('id', $actor->id);
            abort_unless($self !== null, 422);

            return $self;
        }

        $first = $capturableUsers->first();
        abort_unless($first !== null, 403);

        return $first;
    }
}
