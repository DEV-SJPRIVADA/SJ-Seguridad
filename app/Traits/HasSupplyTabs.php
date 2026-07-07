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
        
        return $tabs->map(function ($tab) use ($module, $routeName) {
            $targetRoute = match ($tab) {
                'mis_solicitudes' => 'supplies.index',
                'revision_calidad' => 'supplies.quality.index',
                'gestion_compras' => 'supplies.purchasing.index',
                'catalogo' => 'supplies.products.index',
                default => 'supplies.index',
            };

            $active = match ($tab) {
                'mis_solicitudes' => in_array($routeName, ['supplies.index', 'supplies.show', 'supplies.create'], true),
                'revision_calidad' => str_starts_with((string) $routeName, 'supplies.quality.'),
                'gestion_compras' => str_starts_with((string) $routeName, 'supplies.purchasing.'),
                'catalogo' => str_starts_with((string) $routeName, 'supplies.products.'),
                default => false,
            };

            return [
                'label' => config("access.supply_tabs.{$tab}", ucfirst(str_replace('_', ' ', $tab))),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $active,
            ];
        });
    }
}
