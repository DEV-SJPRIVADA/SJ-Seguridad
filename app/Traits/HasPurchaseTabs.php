<?php

namespace App\Traits;

use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;

trait HasPurchaseTabs
{
    protected function getPurchaseSubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user->purchaseBoardTabsFor($module);
        $routeName = request()->route()?->getName();
        $approvalShowContext = $this->isPurchaseApprovalShowContext();

        return $tabs->map(function ($tab) use ($module, $routeName, $approvalShowContext) {
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
                    || ($routeName === 'purchase-requests.show' && ! $approvalShowContext),
                'pendientes_aprobacion' => str_starts_with((string) $routeName, 'purchase-requests.approval.')
                    || ($routeName === 'purchase-requests.show' && $approvalShowContext),
                'bandeja_compras' => str_starts_with((string) $routeName, 'purchase-requests.processing.'),
                default => false,
            };

            return [
                'label' => config("access.purchase_tabs.{$tab}", ucfirst(str_replace('_', ' ', $tab))),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $active,
            ];
        });
    }

    protected function isPurchaseApprovalShowContext(): bool
    {
        $purchaseRequest = request()->route('purchase_request');
        $user = auth()->user();

        return $purchaseRequest instanceof PurchaseRequest
            && $user !== null
            && $user->can('approve', $purchaseRequest);
    }
}
