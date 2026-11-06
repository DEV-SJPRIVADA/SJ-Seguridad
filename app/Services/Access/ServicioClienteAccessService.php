<?php

namespace App\Services\Access;

use App\Models\User;

class ServicioClienteAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.calidad.servicio_cliente');
    }

    public function canViewFoGi22(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('servicio_cliente.fo_gi_22.view')
            || $user->can('servicio_cliente.fo_gi_22.edit');
    }

    public function canEditFoGi22(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('servicio_cliente.fo_gi_22.edit');
    }

    public function canEditParameters(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('servicio_cliente.parameters.edit');
    }

    /**
     * El dashboard usa el mismo permiso de consulta de FO-GI-22.
     */
    public function canViewDashboard(User $user): bool
    {
        return $this->canViewFoGi22($user);
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        $tabs = [];

        foreach (array_keys(config('access.servicio_cliente_tabs', [])) as $tab) {
            $allowed = match ($tab) {
                'dashboard' => $this->canViewDashboard($user),
                'fo_gi_22' => $this->canViewFoGi22($user),
                'catalogos' => $this->canEditParameters($user),
                default => false,
            };

            if ($allowed) {
                $tabs[] = $tab;
            }
        }

        return $tabs;
    }
}
