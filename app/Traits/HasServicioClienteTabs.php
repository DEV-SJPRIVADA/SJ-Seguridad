<?php

namespace App\Traits;

use App\Services\Access\ServicioClienteAccessService;

trait HasServicioClienteTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getServicioClienteSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(ServicioClienteAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'calidad.servicio-cliente.dashboard',
                'fo_gi_22' => 'calidad.servicio-cliente.fo-gi-22.index',
                'catalogos' => 'calidad.servicio-cliente.catalogos',
                default => 'calidad.servicio-cliente.dashboard',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'calidad.servicio-cliente.dashboard'),
                'fo_gi_22' => $activeTab === 'fo_gi_22'
                    || str_starts_with((string) $routeName, 'calidad.servicio-cliente.fo-gi-22'),
                'catalogos' => $activeTab === 'catalogos'
                    || str_starts_with((string) $routeName, 'calidad.servicio-cliente.catalogos'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.servicio_cliente_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
