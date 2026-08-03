<?php

namespace App\Services\Access;

use App\Models\QualityDocument;
use App\Models\User;

class BoardAccessService
{
    public function canViewDocumentsBoard(User $user, string $areaKey): bool
    {
        if ($user->can("view.area.{$areaKey}") || $user->can("manage.area.{$areaKey}")) {
            return true;
        }

        return $user->area_key === $areaKey && QualityDocument::hasActiveForUser($user->id);
    }

    public function canViewDocumentsBoardForSidebar(User $user, string $areaKey): bool
    {
        if ($user->can('manage.users')) {
            if ($areaKey === 'calidad') {
                return true;
            }

            return $user->hasAssignedArea() && $user->area_key === $areaKey;
        }

        if ($user->can('manage.quality.documents') && $areaKey === 'calidad') {
            return true;
        }

        if ($user->can("view.area.{$areaKey}") || $user->can("manage.area.{$areaKey}")) {
            return true;
        }

        return $user->area_key === $areaKey && QualityDocument::hasActiveForUser($user->id);
    }
}
