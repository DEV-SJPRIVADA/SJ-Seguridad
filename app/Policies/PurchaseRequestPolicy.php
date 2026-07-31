<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->can('manage.users')) {
            return true;
        }

        if ($user->can('purchase.tab.processing')) {
            return true;
        }

        if ((int) $purchaseRequest->user_id === (int) $user->id) {
            return true;
        }

        if ((int) $purchaseRequest->aprobador_id === (int) $user->id && $user->can('purchase.tab.approval')) {
            return true;
        }

        return false;
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase.tab.approval')
            && (int) $purchaseRequest->aprobador_id === (int) $user->id
            && $purchaseRequest->estado === PurchaseRequest::ESTADO_PENDIENTE;
    }

    public function process(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('purchase.tab.processing')
            && $purchaseRequest->estaEnBandejaCompras();
    }
}
