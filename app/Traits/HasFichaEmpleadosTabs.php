<?php

namespace App\Traits;

use App\Services\Access\FichaEmpleadosAccessService;

trait HasFichaEmpleadosTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getFichaEmpleadosSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(FichaEmpleadosAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'empleados' => 'gestion-humana.ficha-empleados.employees.index',
                'catalogos' => 'gestion-humana.ficha-empleados.catalogs.index',
                default => 'gestion-humana.ficha-empleados.employees.index',
            };

            $active = match ($tab) {
                'empleados' => $activeTab === 'empleados'
                    || str_starts_with((string) $routeName, 'gestion-humana.ficha-empleados.employees.'),
                'catalogos' => $activeTab === 'catalogos'
                    || str_starts_with((string) $routeName, 'gestion-humana.ficha-empleados.catalogs.'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.ficha_empleados_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
