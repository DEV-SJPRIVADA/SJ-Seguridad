<?php

namespace App\Services\Access;

use App\Models\User;

class ArchivoAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewArchivoBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.archivo');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('archivo.view') || $user->can('archivo.manage');
    }

    public function canManage(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('archivo.manage');
    }

    public function canExportArchiveTemplate(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $this->canView($user)
            || app(FichaEmpleadosAccessService::class)->canView($user);
    }
}
