<?php

namespace App\Traits;

use App\Services\Access\AcreditacionesAccessService;

trait HasAcreditacionesTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getAcreditacionesSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(AcreditacionesAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'gestion-humana.acreditaciones.dashboard',
                'acreditados' => 'gestion-humana.acreditaciones.acreditados',
                'reporte_diario' => 'gestion-humana.acreditaciones.reporte-diario',
                'validaciones' => 'gestion-humana.acreditaciones.validaciones',
                'export_apo' => 'gestion-humana.acreditaciones.export-apo',
                'catalogo' => 'gestion-humana.acreditaciones.catalogo',
                default => 'gestion-humana.acreditaciones.dashboard',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'gestion-humana.acreditaciones.dashboard'),
                'acreditados' => $activeTab === 'acreditados'
                    || str_starts_with((string) $routeName, 'gestion-humana.acreditaciones.acreditados'),
                'reporte_diario' => $activeTab === 'reporte_diario'
                    || str_starts_with((string) $routeName, 'gestion-humana.acreditaciones.reporte-diario'),
                'validaciones' => $activeTab === 'validaciones'
                    || str_starts_with((string) $routeName, 'gestion-humana.acreditaciones.validaciones'),
                'export_apo' => $activeTab === 'export_apo'
                    || str_starts_with((string) $routeName, 'gestion-humana.acreditaciones.export-apo'),
                'catalogo' => $activeTab === 'catalogo'
                    || str_starts_with((string) $routeName, 'gestion-humana.acreditaciones.catalogo'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.acreditaciones_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
