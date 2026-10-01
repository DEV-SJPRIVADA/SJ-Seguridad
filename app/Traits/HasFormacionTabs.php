<?php

namespace App\Traits;

use App\Services\Access\FormacionAccessService;

trait HasFormacionTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getFormacionSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(FormacionAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'dashboard' => 'gestion-humana.formacion.dashboard',
                default => 'gestion-humana.formacion.formaciones',
            };

            $active = match ($tab) {
                'dashboard' => $activeTab === 'dashboard'
                    || str_starts_with((string) $routeName, 'gestion-humana.formacion.dashboard'),
                'formaciones' => $activeTab === 'formaciones'
                    || str_starts_with((string) $routeName, 'gestion-humana.formacion.formaciones'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.formacion_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
