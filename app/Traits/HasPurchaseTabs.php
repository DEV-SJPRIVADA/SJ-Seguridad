<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasPurchaseTabs
{
    protected function getPurchaseSubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user->purchaseBoardTabsFor($module);
        $routeName = request()->route()?->getName();

        return $tabs->map(function ($tab) use ($module, $routeName) {
            $targetRoute = match ($tab) {
                'nueva' => 'purchase-requests.create',
                'mis_solicitudes' => 'purchase-requests.index',
                'pendientes_aprobacion' => 'purchase-requests.approval.index',
                'bandeja_compras' => 'purchase-requests.processing.index',
                default => 'purchase-requests.index',
            };

            $active = match ($tab) {
                'nueva' => in_array($routeName, ['purchase-requests.create'], true),
                'mis_solicitudes' => in_array($routeName, ['purchase-requests.index', 'purchase-requests.show'], true),
                'pendientes_aprobacion' => str_starts_with((string) $routeName, 'purchase-requests.approval.'),
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
}
