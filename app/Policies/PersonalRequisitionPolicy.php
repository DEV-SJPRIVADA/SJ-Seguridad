<?php

namespace App\Policies;

use App\Models\PersonalRequisition;
use App\Models\User;
use App\Services\Access\RequisitionAccessService;

class PersonalRequisitionPolicy
{
    public function viewInModule(User $user, PersonalRequisition $requisition, string $module): bool
    {
        return app(RequisitionAccessService::class)->canAccessRequisitionRecord(
            $user,
            $module,
            $requisition->requesting_area_key
        );
    }
}
