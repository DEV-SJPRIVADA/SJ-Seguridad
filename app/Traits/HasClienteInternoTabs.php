<?php

namespace App\Traits;

use App\Services\Access\ClienteInternoAccessService;

trait HasClienteInternoTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getClienteInternoSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(ClienteInternoAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'gestion-humana.cliente-interno.dashboard',
                'solicitudes' => 'gestion-humana.cliente-interno.solicitudes',
                'catalogos' => 'gestion-humana.cliente-interno.catalogos',
                default => 'gestion-humana.cliente-interno.dashboard',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'gestion-humana.cliente-interno.dashboard'),
                'solicitudes' => $activeTab === 'solicitudes'
                    || str_starts_with((string) $routeName, 'gestion-humana.cliente-interno.solicitudes'),
                'catalogos' => $activeTab === 'catalogos'
                    || str_starts_with((string) $routeName, 'gestion-humana.cliente-interno.catalogos'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.cliente_interno_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
