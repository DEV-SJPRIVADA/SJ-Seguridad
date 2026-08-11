<?php

namespace App\Services\Access;

use App\Models\User;

class BoardAccessService
{
    public function canViewDocumentsBoard(User $user, string $areaKey): bool
    {
        if ($user->can('manage.quality.documents') && $areaKey === 'calidad') {
            return true;
        }

        return $user->hasAssignedArea() && $user->area_key === $areaKey;
    }

    public function canViewDocumentsBoardForSidebar(User $user, string $areaKey): bool
    {
        if ($user->can('manage.users')) {
            if ($areaKey === 'calidad') {
                return true;
            }

            return $user->hasAssignedArea() && $user->area_key === $areaKey;
        }

        return $this->canViewDocumentsBoard($user, $areaKey);
    }
}
