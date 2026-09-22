<?php

namespace App\Services\Access;

use App\Models\User;

class SeleccionAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewSeleccionBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.seleccion');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('seleccion.view') || $user->can('seleccion.edit');
    }

    public function canEdit(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('seleccion.edit');
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->canView($user)) {
            return [];
        }

        $tabs = array_keys(config('access.seleccion_tabs', []));

        if (! $this->canEdit($user)) {
            $tabs = array_values(array_filter($tabs, fn (string $tab): bool => $tab !== 'catalogos'));
        }

        return $tabs;
    }
}
