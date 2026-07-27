<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IndicadorNavigation
{
    /**
     * @return array<string, array{key: string, label: string, route: string, patterns: array<int, string>, visible: bool}>
     */
    private static function tabDefinitions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return [
            'dashboard' => [
                'key' => 'dashboard',
                'label' => config('access.indicador_tabs.dashboard'),
                'route' => 'indicadores.dashboard',
                'patterns' => ['indicadores.dashboard'],
                'visible' => $user->can('operations.view') || $user->can('operations.manage'),
            ],
            'captura' => [
                'key' => 'captura',
                'label' => config('access.indicador_tabs.captura'),
                'route' => 'indicadores.index',
                'patterns' => ['indicadores.index', 'indicadores.show'],
                'visible' => $user->can('operations.capture') || $user->can('operations.manage'),
            ],
            'consolidado' => [
                'key' => 'consolidado',
                'label' => config('access.indicador_tabs.consolidado'),
                'route' => 'indicadores.admin.consolidado.index',
                'patterns' => ['indicadores.admin.consolidado.*'],
                'visible' => $user->can('operations.manage'),
            ],
            'ajustes' => [
                'key' => 'ajustes',
                'label' => config('access.indicador_tabs.ajustes'),
                'route' => 'indicadores.admin.ajustes',
                'patterns' => [
                    'indicadores.admin.ajustes',
                    'indicadores.admin.periods.*',
                    'indicadores.admin.metas*',
                    'indicadores.admin.weights*',
                    'indicadores.admin.capturadores*',
                    'indicadores.admin.audit.*',
                ],
                'visible' => $user->can('operations.manage'),
            ],
        ];
    }

    /**
     * @return Collection<int, array{label: string, url: string, active: bool}>
     */
    public static function subTabs(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        $routeName = (string) request()->route()?->getName();
        $definitions = self::tabDefinitions($user);

        return collect(array_keys(config('access.indicador_tabs', [])))
            ->filter(fn (string $key) => isset($definitions[$key]) && $definitions[$key]['visible'])
            ->map(function (string $key) use ($definitions, $routeName): array {
                $tab = $definitions[$key];
                $patterns = $tab['patterns'] ?? [$tab['route']];
                $active = collect($patterns)->contains(fn (string $pattern) => Str::is($pattern, $routeName));

                return [
                    'label' => $tab['label'],
                    'url' => route($tab['route']),
                    'active' => $active,
                ];
            })
            ->values();
    }
}
