<?php

namespace App\Services\GestionHumana;

use App\Models\User;

class PlantillasWordAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewPlantillasWordBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.gestion_humana.plantillas_word');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('plantillas_word.view') || $user->can('plantillas_word.manage');
    }

    public function canManage(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('plantillas_word.manage');
    }
}
