<?php

namespace App\Policies;

use App\Models\QualityDocument;
use App\Models\User;

class QualityDocumentPolicy
{
    public function manage(User $user): bool
    {
        return $user->can('manage.quality.documents');
    }

    public function viewInLibrary(User $user, QualityDocument $document, string $module): bool
    {
        if (! $document->is_active || ! $document->isAssignedToArea($module)) {
            return false;
        }

        return $user->can("view.area.{$module}") || $user->can("manage.area.{$module}");
    }

    public function viewPersonal(User $user, QualityDocument $document): bool
    {
        return $document->is_active && $document->isAssignedToUser($user->id);
    }
}
