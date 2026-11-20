<?php

namespace App\Services\Access;

use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Models\User;

/**
 * Visibilidad del tablero Matrices en el sidebar.
 *
 * La matriz se muestra en el área del usuario (igual que Documentos de Calidad).
 * Basta con tener el permiso transversal o al menos un otorgamiento vigente.
 */
class MatrixAccessService
{
    public function canViewBoard(User $user, string $areaKey): bool
    {
        if (! $user->hasAssignedArea() || $user->area_key !== $areaKey) {
            return false;
        }

        return $this->hasAnyAccess($user);
    }

    public function hasAnyAccess(User $user): bool
    {
        if ($user->getAllPermissions()->contains('name', 'matrices.manage')) {
            return true;
        }

        if (MatrixUserAccess::query()->where('user_id', $user->id)->active()->exists()) {
            return true;
        }

        if (! $user->hasAssignedArea()) {
            return false;
        }

        return MatrixAreaAccess::query()
            ->where('area_key', $user->area_key)
            ->active()
            ->exists();
    }
}
