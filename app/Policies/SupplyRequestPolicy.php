<?php

namespace App\Policies;

use App\Models\SupplyRequest;
use App\Models\User;

class SupplyRequestPolicy
{
    public function viewInModule(User $user, SupplyRequest $supplyRequest, string $module): bool
    {
        return $supplyRequest->area_key === $module
            && $user->canAccessSupplyTab($module, 'my_requests');
    }

    public function reviewApproval(User $user, SupplyRequest $supplyRequest, string $module): bool
    {
        return $supplyRequest->area_key === $module
            && $user->canAccessSupplyTab($module, 'quality');
    }
}
