<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasCatalogTabs
{
    /**
     * Subnav del tablero Catálogos: Proveedores e Insumos, con las rutas que ya existen.
     *
     * @return Collection<int, array{label: string, url: string, active: bool}>
     */
    protected function getCatalogSubTabs(string $module): Collection
    {
        $user = auth()->user();
        $routeName = request()->route()?->getName();

        return $user->catalogBoardTabs()->map(function (string $tab) use ($module, $routeName): array {
            $targetRoute = match ($tab) {
                'proveedores' => 'purchase-requests.providers.index',
                'insumos' => 'supplies.products.index',
                default => 'purchase-requests.providers.index',
            };

            $active = match ($tab) {
                'proveedores' => str_starts_with((string) $routeName, 'purchase-requests.providers.'),
                'insumos' => str_starts_with((string) $routeName, 'supplies.products.'),
                default => false,
            };

            $label = match ($tab) {
                'proveedores' => 'Proveedores',
                'insumos' => 'Insumos',
                default => $tab,
            };

            return [
                'label' => $label,
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $active,
            ];
        });
    }
}
