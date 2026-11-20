<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Models\User;

final class MatrixAccessResolver
{
    /**
     * Resuelve el acceso efectivo del usuario a la matriz.
     *
     * Reglas:
     * - Super-admin o permiso transversal `matrices.manage`: acceso total.
     * - Creador: acceso total.
     * - Otorgamientos por usuario y por área: se unen y gana el más permisivo.
     * - `can_edit_others` implica `can_view_others`.
     */
    public function resolve(?User $user, Matrix $matrix): MatrixAccess
    {
        if (! $user instanceof User) {
            return MatrixAccess::none();
        }

        if ($this->isManager($user) || (int) $matrix->owner_user_id === (int) $user->id) {
            return MatrixAccess::owner();
        }

        $userGrant = MatrixUserAccess::query()
            ->where('matrix_id', $matrix->id)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        $areaGrant = null;

        if ($user->hasAssignedArea()) {
            $areaGrant = MatrixAreaAccess::query()
                ->where('matrix_id', $matrix->id)
                ->where('area_key', $user->area_key)
                ->active()
                ->first();
        }

        if ($userGrant === null && $areaGrant === null) {
            return MatrixAccess::none();
        }

        $canRegister = (bool) ($userGrant?->can_register) || (bool) ($areaGrant?->can_register);
        $canEditOthers = (bool) ($userGrant?->can_edit_others) || (bool) ($areaGrant?->can_edit_others);
        $canViewOthers = (bool) ($userGrant?->can_view_others) || (bool) ($areaGrant?->can_view_others);

        if ($canEditOthers) {
            $canViewOthers = true;
        }

        return new MatrixAccess(
            canView: true,
            canRegister: $canRegister,
            canViewOthers: $canViewOthers,
            canEditOthers: $canEditOthers,
            canManageDefinition: false,
        );
    }

    public function isManager(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->getAllPermissions()->contains('name', 'matrices.manage');
    }
}
