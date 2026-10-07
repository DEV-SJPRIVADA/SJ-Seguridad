<?php

namespace App\Services\Access;

use App\Models\User;

class ClienteInternoAccessService
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

        return $user->can('view.board.gestion_humana.cliente_interno');
    }

    public function canViewSolicitudes(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('cliente_interno.solicitudes.view')
            || $user->can('cliente_interno.solicitudes.edit');
    }

    public function canEditSolicitudes(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('cliente_interno.solicitudes.edit');
    }

    public function canViewCartasVacaciones(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('cliente_interno.cartas_vacaciones.view')
            || $user->can('cliente_interno.cartas_vacaciones.edit');
    }

    public function canEditCartasVacaciones(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('cliente_interno.cartas_vacaciones.edit');
    }

    public function canEditParameters(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('cliente_interno.parameters.edit');
    }

    /**
     * Dashboard: solicitudes o catálogos. No incluye permisos de Cartas Vacaciones.
     */
    public function canViewDashboard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $this->canViewSolicitudes($user) || $this->canEditParameters($user);
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        $tabs = [];

        foreach (array_keys(config('access.cliente_interno_tabs', [])) as $tab) {
            $allowed = match ($tab) {
                'dashboard' => $this->canViewDashboard($user),
                'solicitudes' => $this->canViewSolicitudes($user),
                'cartas_vacaciones' => $this->canViewCartasVacaciones($user),
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
