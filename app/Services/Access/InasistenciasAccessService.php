<?php

namespace App\Services\Access;

use App\Models\User;

/**
 * Acceso al tablero Inasistencias (GH): board + view + edit (edit implica view).
 */
class InasistenciasAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    /**
     * Sidebar visible con board, view, edit o bypass admin.
     */
    public function canViewBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.inasistencias')
            || $this->canView($user);
    }

    /**
     * Entrar al tablero, histórico y export.
     */
    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('inasistencias.view') || $user->can('inasistencias.edit');
    }

    /**
     * Generar cartas (implica view).
     */
    public function canEdit(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('inasistencias.edit');
    }

    /**
     * @return list<string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->canView($user)) {
            return [];
        }

        $tabs = array_keys(config('access.inasistencias_tabs', []));

        if (! $this->canEdit($user)) {
            $tabs = array_values(array_filter($tabs, static fn (string $tab): bool => $tab !== 'generar'));
        }

        return $tabs;
    }
}
