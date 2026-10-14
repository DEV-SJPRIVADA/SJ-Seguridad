<?php

namespace App\Traits;

use App\Services\Access\InasistenciasAccessService;

trait HasInasistenciasTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getInasistenciasSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(InasistenciasAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'generar' => 'gestion-humana.inasistencias.generar',
                default => 'gestion-humana.inasistencias.historico',
            };

            $active = match ($tab) {
                'generar' => $activeTab === 'generar'
                    || str_starts_with((string) $routeName, 'gestion-humana.inasistencias.generar')
                    || (string) $routeName === 'gestion-humana.inasistencias.lookup'
                    || (string) $routeName === 'gestion-humana.inasistencias.generate',
                'historico' => $activeTab === 'historico'
                    || str_starts_with((string) $routeName, 'gestion-humana.inasistencias.historico'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.inasistencias_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
