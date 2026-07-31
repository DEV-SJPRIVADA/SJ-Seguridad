<?php

namespace App\Services\Access;

use App\Models\User;

class FichaEmpleadosAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewFichaEmpleadosBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($user->can('view.board.gestion_humana.ficha_empleados')) {
            return true;
        }

        return false;
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('ficha_empleados.view') || $user->can('ficha_empleados.manage');
    }

    public function canManage(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('ficha_empleados.manage');
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->canView($user)) {
            return [];
        }

        $tabs = array_keys(config('access.ficha_empleados_tabs', []));

        if (! $this->canManage($user)) {
            $tabs = array_values(array_filter($tabs, fn (string $tab): bool => $tab !== 'catalogos'));
        }

        return $tabs;
    }
}
