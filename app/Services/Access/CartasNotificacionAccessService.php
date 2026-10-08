<?php

namespace App\Services\Access;

use App\Models\User;

/**
 * Acceso al tablero Cartas Notificación (GH).
 * Solo existe cartas_notificacion.edit (sin .view): edit implica entrar y generar.
 */
class CartasNotificacionAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    /**
     * Sidebar visible con board, con edit, o bypass admin.
     */
    public function canViewBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.cartas_notificacion')
            || $user->can('cartas_notificacion.edit');
    }

    /**
     * Pantalla y acciones (lookup, Excel, generate): solo edit o bypass.
     */
    public function canEdit(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('cartas_notificacion.edit');
    }
}
