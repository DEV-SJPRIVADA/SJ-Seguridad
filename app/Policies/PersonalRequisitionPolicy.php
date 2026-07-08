<?php

namespace App\Policies;

use App\Models\PersonalRequisition;
use App\Models\User;

class PersonalRequisitionPolicy
{
    public function viewInModule(User $user, PersonalRequisition $requisition, string $module): bool
    {
        $canManageAll = $user->can('manage.users') || $user->can('manage.area.gestion_humana');

        return $canManageAll || $requisition->requesting_area_key === $module;
    }
}
