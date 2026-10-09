<?php

namespace App\Services\Access;

use App\Models\User;

class MtSt04AccessService
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

        return $user->can('view.board.gestion_humana.mt_st_04');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('mt_st_04.view') || $user->can('mt_st_04.edit');
    }

    public function canEdit(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('mt_st_04.edit');
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->canView($user)) {
            return [];
        }

        return array_keys(config('access.mt_st_04_tabs', []));
    }
}
