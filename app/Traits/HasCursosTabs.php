<?php

namespace App\Traits;

use App\Services\Access\CursosAccessService;

trait HasCursosTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getCursosSubTabs(string $activeTab): array
    {
        $user = auth()->user();
        $tabs = app(CursosAccessService::class)->visibleTabsFor($user);
        $routeName = request()->route()?->getName();

        return collect($tabs)->map(function (string $tab) use ($activeTab, $routeName): array {
            $targetRoute = match ($tab) {
                'catalogo' => 'gestion-humana.cursos.catalogo',
                default => 'gestion-humana.cursos.registros',
            };

            $active = match ($tab) {
                'registros' => $activeTab === 'registros'
                    || str_starts_with((string) $routeName, 'gestion-humana.cursos.registros'),
                'catalogo' => $activeTab === 'catalogo'
                    || str_starts_with((string) $routeName, 'gestion-humana.cursos.catalogo'),
                default => $tab === $activeTab,
            };

            return [
                'label' => config("access.cursos_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute),
                'active' => $active,
            ];
        })->values()->all();
    }
}
