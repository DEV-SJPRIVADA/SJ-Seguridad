<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IndicadorNavigation
{
    /**
     * @return \Illuminate\Support\Collection<int, array{label: string, url: string, active: bool}>
     */
    public static function subTabs(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        $routeName = (string) request()->route()?->getName();

        $definitions = [
            [
                'key' => 'dashboard',
                'label' => config('access.indicador_tabs.dashboard'),
                'route' => 'indicadores.dashboard',
                'patterns' => ['indicadores.dashboard'],
                'visible' => $user->can('operations.view') || $user->can('operations.manage'),
            ],
            [
                'key' => 'captura',
                'label' => config('access.indicador_tabs.captura'),
                'route' => 'indicadores.index',
                'patterns' => ['indicadores.index', 'indicadores.show'],
                'visible' => $user->can('operations.capture') || $user->can('operations.manage'),
            ],
            [
                'key' => 'ajustes',
                'label' => config('access.indicador_tabs.ajustes'),
                'route' => 'indicadores.admin.ajustes',
                'patterns' => [
                    'indicadores.admin.ajustes',
                    'indicadores.admin.periods.*',
                    'indicadores.admin.weights*',
                    'indicadores.admin.audit.*',
                ],
                'visible' => $user->can('operations.manage'),
            ],
            [
                'key' => 'madre',
                'label' => config('access.indicador_tabs.madre'),
                'route' => 'indicadores.admin.mother.index',
                'patterns' => ['indicadores.admin.mother.*'],
                'visible' => $user->can('operations.manage'),
            ],
        ];

        return collect($definitions)
            ->filter(fn (array $tab) => $tab['visible'])
            ->map(function (array $tab) use ($routeName): array {
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
