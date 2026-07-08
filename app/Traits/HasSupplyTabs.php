<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasSupplyTabs
{
    protected function getSupplySubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user->supplyBoardTabsFor($module);
        $routeName = request()->route()?->getName();

        return $tabs->map(function ($tab) use ($module, $routeName) {
            $targetRoute = match ($tab) {
                'mis_solicitudes' => 'supplies.index',
                'aprobacion_insumos' => 'supplies.approval.index',
                'insumos_aprobados' => 'supplies.approved.index',
                'catalogo' => 'supplies.products.index',
                default => 'supplies.index',
            };

            $active = match ($tab) {
                'mis_solicitudes' => in_array($routeName, ['supplies.index', 'supplies.show', 'supplies.create'], true),
                'aprobacion_insumos' => str_starts_with((string) $routeName, 'supplies.approval.'),
                'insumos_aprobados' => str_starts_with((string) $routeName, 'supplies.approved.'),
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
