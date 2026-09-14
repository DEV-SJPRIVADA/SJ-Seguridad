<?php

namespace App\Services\Access;

use App\Models\User;

class DesvinculacionesAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewDesvinculacionesBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.desvinculaciones');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('desvinculaciones.view');
    }

    public function canMasivos(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('desvinculaciones.masivos');
    }

    public function canEditSeguimientos(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('desvinculaciones.seguimientos.edit');
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->canView($user)) {
            return [];
        }

        return array_keys(config('access.desvinculaciones_tabs', []));
    }
}
