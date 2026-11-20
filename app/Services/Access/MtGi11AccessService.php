<?php

namespace App\Services\Access;

use App\Models\User;

/**
 * Acceso al tablero MT-GI-11 (Calidad).
 *
 * Reglas acordadas:
 * - `mt_gi_11.matriz.edit` implica `view`.
 * - El Dashboard no tiene clave propia: se abre con `matriz.view`.
 * - `mt_gi_11.view_all` amplía el alcance a todas las solicitudes (Calidad / admin).
 * - `mt_gi_11.parameters.edit` habilita la pestaña Catálogos.
 */
class MtGi11AccessService
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

        return $user->can('view.board.calidad.mt_gi_11');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('mt_gi_11.matriz.view') || $user->can('mt_gi_11.matriz.edit');
    }

    public function canEdit(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('mt_gi_11.matriz.edit');
    }

    /**
     * Alcance del listado: `true` = ve todas las solicitudes del tablero.
     * Editar sin `view_all` sigue limitado a las solicitudes propias.
     */
    public function canViewAll(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('mt_gi_11.view_all');
    }

    public function canEditParameters(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('mt_gi_11.parameters.edit');
    }

    /**
     * Puede operar (editar / eliminar / adjuntar) esta solicitud concreta.
     */
    public function canManageSolicitud(User $user, int $createdBy): bool
    {
        if ($this->isAdminBypass($user) || $this->canViewAll($user)) {
            return true;
        }

        return $this->canEdit($user) && (int) $createdBy === (int) $user->id;
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        $tabs = [];

        foreach (array_keys(config('access.mt_gi_11_tabs', [])) as $tab) {
            $allowed = match ($tab) {
                'dashboard' => $this->canView($user),
                'matriz' => $this->canView($user),
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
