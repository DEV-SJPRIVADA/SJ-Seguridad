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
                'seguimiento_historial' => 'supplies.seguimiento.historial',
                'seguimiento_ordenes' => 'supplies.seguimiento.ordenes',
                'seguimiento_gasto' => 'supplies.seguimiento.gasto',
                default => 'supplies.index',
            };

            $active = match ($tab) {
                'mis_solicitudes' => in_array($routeName, ['supplies.index', 'supplies.show', 'supplies.create'], true),
                'aprobacion_insumos' => str_starts_with((string) $routeName, 'supplies.approval.'),
                'insumos_aprobados' => str_starts_with((string) $routeName, 'supplies.approved.'),
                'catalogo' => str_starts_with((string) $routeName, 'supplies.products.'),
                'seguimiento_historial' => str_starts_with((string) $routeName, 'supplies.seguimiento.historial'),
                'seguimiento_ordenes' => str_starts_with((string) $routeName, 'supplies.seguimiento.ordenes'),
                'seguimiento_gasto' => str_starts_with((string) $routeName, 'supplies.seguimiento.gasto'),
                default => false,
            };

            $query = [];
            if (str_starts_with($tab, 'seguimiento_')) {
                $query = array_filter([
                    'year' => request('year'),
                    'month' => request('month'),
                    'area_key' => request('area_key'),
                    'sede_id' => request('sede_id'),
                    'product' => request('product'),
                ], fn ($value) => $value !== null && $value !== '');
            }

            return [
                'label' => config("access.supply_tabs.{$tab}", ucfirst(str_replace('_', ' ', $tab))),
                'url' => route($targetRoute, array_merge(['module' => $module], $query)),
                'active' => $active,
            ];
        });
    }
}
