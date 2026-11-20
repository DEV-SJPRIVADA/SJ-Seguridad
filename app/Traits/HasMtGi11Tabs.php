<?php

namespace App\Traits;

use App\Services\Access\MtGi11AccessService;

trait HasMtGi11Tabs
{
    /**
     * Pestañas visibles del tablero MT-GI-11 para el usuario autenticado.
     *
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getMtGi11SubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(MtGi11AccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'calidad.mt-gi-11.dashboard',
                'matriz' => 'calidad.mt-gi-11.matriz',
                'catalogos' => 'calidad.mt-gi-11.catalogos',
                default => 'calidad.mt-gi-11.dashboard',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'calidad.mt-gi-11.dashboard'),
                'matriz' => $activeTab === 'matriz'
                    || str_starts_with((string) $routeName, 'calidad.mt-gi-11.matriz'),
                'catalogos' => $activeTab === 'catalogos'
                    || str_starts_with((string) $routeName, 'calidad.mt-gi-11.catalogos'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.mt_gi_11_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
