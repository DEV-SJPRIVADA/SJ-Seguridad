<?php

namespace App\Services\Access;

use App\Models\User;

class CommercialAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewGestionClientesBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($user->can('view.board.comercial.gestion_clientes')) {
            return true;
        }

        if ($user->can('comercial.matriz.view') || $user->can('comercial.matriz.manage')) {
            return true;
        }

        return $user->can('view.board.comercial.matriz_clientes')
            || $user->can('view.board.comercial.servicios_comerciales')
            || $user->can('manage.commercial.parameters');
    }

    public function canAccessTab(User $user, string $tab): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($tab === 'parametros') {
            return $user->can('manage.commercial.parameters')
                || $user->can('comercial.matriz.manage');
        }

        if ($user->can('comercial.matriz.view') || $user->can('comercial.matriz.manage')) {
            return true;
        }

        return match ($tab) {
            'clientes' => $user->can('view.board.comercial.matriz_clientes'),
            'servicios' => $user->can('view.board.comercial.servicios_comerciales'),
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        $tabs = [];

        foreach (array_keys(config('access.gestion_clientes_tabs', [])) as $tab) {
            if ($this->canAccessTab($user, $tab)) {
                $tabs[] = $tab;
            }
        }

        return $tabs;
    }
}
