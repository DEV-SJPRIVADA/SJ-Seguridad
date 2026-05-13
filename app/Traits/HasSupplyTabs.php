<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasSupplyTabs
{
    /**
     * @param string $module
     * @return Collection
     */
    protected function getSupplySubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user->supplyBoardTabsFor($module);
        $routeName = request()->route()?->getName();
        
        return $tabs->map(function($tab) use ($module, $routeName) {
            $targetRoute = match($tab) {
                'mis_solicitudes' => 'supplies.index',
                'revision_calidad' => 'supplies.quality.index',
                'gestion_compras' => 'supplies.purchasing.index',
                'catalogo' => 'supplies.products.index',
                default => 'supplies.index',
            };

            return [
                'label' => config("access.supply_tabs.{$tab}", ucfirst(str_replace('_', ' ', $tab))),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $routeName === $targetRoute,
            ];
        });
    }
}
