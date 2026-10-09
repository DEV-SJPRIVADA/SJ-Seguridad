<?php

namespace App\Traits;

use App\Services\Access\MtSt04AccessService;

trait HasMtSt04Tabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getMtSt04SubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(MtSt04AccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'gestion-humana.mt-st-04.dashboard',
                default => 'gestion-humana.mt-st-04.matriz',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'gestion-humana.mt-st-04.dashboard'),
                'matriz' => $activeTab === 'matriz'
                    || str_starts_with((string) $routeName, 'gestion-humana.mt-st-04.matriz'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.mt_st_04_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
