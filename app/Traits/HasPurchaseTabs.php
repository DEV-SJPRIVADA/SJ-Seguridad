<?php

namespace App\Traits;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Collection;

trait HasPurchaseTabs
{
    protected function getPurchaseSubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user->purchaseBoardTabsFor($module);
        $routeName = request()->route()?->getName();
        $showTabContext = $this->resolvePurchaseShowTabContext();

        return $tabs->map(function ($tab) use ($module, $routeName, $showTabContext) {
            $targetRoute = match ($tab) {
                'nueva' => 'purchase-requests.create',
                'mis_solicitudes' => 'purchase-requests.index',
                'pendientes_aprobacion' => 'purchase-requests.approval.index',
                'bandeja_compras' => 'purchase-requests.processing.index',
                default => 'purchase-requests.index',
            };

            $active = match ($tab) {
                'nueva' => in_array($routeName, ['purchase-requests.create'], true),
                'mis_solicitudes' => $routeName === 'purchase-requests.index'
                    || in_array($routeName, ['purchase-requests.edit', 'purchase-requests.update'], true)
                    || ($routeName === 'purchase-requests.show' && $showTabContext === 'mis_solicitudes'),
                'pendientes_aprobacion' => str_starts_with((string) $routeName, 'purchase-requests.approval.')
                    || ($routeName === 'purchase-requests.show' && $showTabContext === 'approval'),
                'bandeja_compras' => str_starts_with((string) $routeName, 'purchase-requests.processing.')
                    || ($routeName === 'purchase-requests.show' && $showTabContext === 'processing'),
                default => false,
            };

            return [
                'label' => config("access.purchase_tabs.{$tab}", ucfirst(str_replace('_', ' ', $tab))),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $active,
            ];
        });
    }

    /**
     * Contexto de pestaña al ver detalle. Preferir ?from=; no usar Gate::can('approve')
     * porque super-admin pasa todas las abilities vía Gate::before.
     *
     * @return 'mis_solicitudes'|'approval'|'processing'|null
     */
    protected function resolvePurchaseShowTabContext(): ?string
    {
        $from = request()->query('from');

        if (in_array($from, ['mis_solicitudes', 'approval', 'processing'], true)) {
            return $from;
        }

        $purchaseRequest = request()->route('purchase_request');
        $user = auth()->user();

        if (! $purchaseRequest instanceof PurchaseRequest || $user === null) {
            return null;
        }

        if ((int) $purchaseRequest->user_id === (int) $user->id) {
            return 'mis_solicitudes';
        }

        if ($this->isAssignedPendingApprover($user, $purchaseRequest)) {
            return 'approval';
        }

        if ($user->hasRole('super-admin') || $user->can('purchase.tab.processing')) {
            return 'processing';
        }

        return 'mis_solicitudes';
    }

    protected function isAssignedPendingApprover(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($purchaseRequest->estado !== PurchaseRequest::ESTADO_PENDIENTE) {
            return false;
        }

        if ((int) $purchaseRequest->aprobador_id !== (int) $user->id) {
            return false;
        }

        return $user->hasRole('super-admin') || $user->can('purchase.tab.approval');
    }
}
