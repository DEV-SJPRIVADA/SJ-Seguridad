<?php

namespace App\Traits;

use App\Services\Access\CommercialAccessService;

trait HasGestionClientesTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getGestionClientesSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(CommercialAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'clientes' => 'comercial.matriz.clients.index',
                'servicios' => 'comercial.matriz.services.index',
                'parametros' => 'comercial.parameters.index',
                default => 'comercial.matriz.clients.index',
            };

            $active = match ($tab) {
                'clientes' => $activeTab === 'clientes'
                    || str_starts_with((string) $routeName, 'comercial.matriz.clients.'),
                'servicios' => $activeTab === 'servicios'
                    || str_starts_with((string) $routeName, 'comercial.matriz.services.'),
                'parametros' => $activeTab === 'parametros'
                    || str_starts_with((string) $routeName, 'comercial.parameters.'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.gestion_clientes_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
