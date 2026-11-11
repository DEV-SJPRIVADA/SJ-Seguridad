<?php

namespace App\Traits;

use App\Services\Access\PlaneacionEstrategicaAccessService;

trait HasPlaneacionTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getPlaneacionSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(PlaneacionEstrategicaAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'calidad.planeacion.dashboard',
                'indicadores' => 'calidad.planeacion.indicadores',
                'captura' => 'calidad.planeacion.captura',
                'estrategia' => 'calidad.planeacion.estrategia',
                default => 'calidad.planeacion.indicadores',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || (string) $routeName === 'calidad.planeacion.dashboard',
                'indicadores' => $activeTab === 'indicadores'
                    || str_starts_with((string) $routeName, 'calidad.planeacion.indicadores'),
                'captura' => $activeTab === 'captura'
                    || str_starts_with((string) $routeName, 'calidad.planeacion.captura'),
                'estrategia' => $activeTab === 'estrategia'
                    || str_starts_with((string) $routeName, 'calidad.planeacion.estrategia'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.planeacion_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
