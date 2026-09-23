<?php

namespace App\Services\Access;

use App\Models\User;

class AcreditacionesAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewAcreditacionesBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.acreditaciones');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('acreditaciones.view') || $user->can('acreditaciones.edit');
    }

    public function canEdit(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('acreditaciones.edit');
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->canView($user)) {
            return [];
        }

        $tabs = array_keys(config('access.acreditaciones_tabs', []));

        if (! $this->canEdit($user)) {
            $tabs = array_values(array_filter($tabs, fn (string $tab): bool => $tab !== 'catalogo'));
        }

        return $tabs;
    }
}
