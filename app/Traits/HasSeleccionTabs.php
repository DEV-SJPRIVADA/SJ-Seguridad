<?php

namespace App\Traits;

use App\Services\Access\SeleccionAccessService;

trait HasSeleccionTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getSeleccionSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(SeleccionAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'gestion-humana.seleccion.dashboard',
                'ingresos' => 'gestion-humana.seleccion.ingresos',
                'examenes' => 'gestion-humana.seleccion.examenes',
                'catalogos' => 'gestion-humana.seleccion.catalogos',
                default => 'gestion-humana.seleccion.dashboard',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'gestion-humana.seleccion.dashboard'),
                'ingresos' => $activeTab === 'ingresos'
                    || str_starts_with((string) $routeName, 'gestion-humana.seleccion.ingresos'),
                'examenes' => $activeTab === 'examenes'
                    || str_starts_with((string) $routeName, 'gestion-humana.seleccion.examenes'),
                'catalogos' => $activeTab === 'catalogos'
                    || str_starts_with((string) $routeName, 'gestion-humana.seleccion.catalogos'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.seleccion_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
