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

        return $this->canViewClients($user)
            || $this->canViewServices($user)
            || $this->canEditParameters($user);
    }

    public function canViewDashboard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.comercial.dashboard');
    }

    public function canViewClients(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('comercial.clients.view')
            || $user->can('comercial.clients.edit');
    }

    public function canEditClients(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('comercial.clients.edit');
    }

    public function canViewServices(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('comercial.services.view')
            || $user->can('comercial.services.edit');
    }

    public function canEditServices(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('comercial.services.edit');
    }

    public function canEditParameters(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('comercial.parameters.edit');
    }

    public function canAccessTab(User $user, string $tab): bool
    {
        return match ($tab) {
            'clientes' => $this->canViewClients($user),
            'servicios' => $this->canViewServices($user),
            'parametros' => $this->canEditParameters($user),
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
