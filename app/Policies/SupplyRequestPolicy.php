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

    /**
     * Calidad revisa suministros de todas las áreas desde el tablero donde tiene la pestaña Por aprobar.
     */
    public function reviewApproval(User $user, SupplyRequest $supplyRequest, string $module): bool
    {
        return $user->canAccessSupplyTab($module, 'quality');
    }

    public function resubmit(User $user, SupplyRequest $supplyRequest): bool
    {
        return (int) $supplyRequest->user_id === (int) $user->id
            && $supplyRequest->status === 'rechazada_calidad'
            && $user->can('supply.tab.my_requests');
    }

    /**
     * Hilo de notas. Queda cerrado cuando el suministro ya está completado.
     */
    public function comment(User $user, SupplyRequest $supplyRequest): bool
    {
        if (! $supplyRequest->puedeComentar()) {
            return false;
        }

        if ($user->can('manage.users') || $user->can('purchase.tab.processing')) {
            return true;
        }

        if ((int) $supplyRequest->user_id === (int) $user->id) {
            return true;
        }

        return $user->can('supply.tab.quality') || $user->can('approve.supply.quality');
    }
}
