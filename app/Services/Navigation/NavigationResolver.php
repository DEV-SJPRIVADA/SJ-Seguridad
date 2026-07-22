<?php

namespace App\Services\Navigation;

use App\Models\User;
use App\Services\Access\BoardAccessService;
use App\Services\Access\RequisitionAccessService;
use App\Services\Access\SupplyAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationResolver
{
    public function __construct(
        private readonly RequisitionAccessService $requisitionAccess,
        private readonly SupplyAccessService $supplyAccess,
        private readonly BoardAccessService $boardAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(?User $user = null, ?string $routeName = null, ?string $requestModule = null, ?string $requestBoard = null): array
    {
        $user ??= auth()->user();
        $routeName ??= request()->route()?->getName();
        $requestModule ??= $this->resolveRequestModule($routeName);
        $requestBoard ??= request()->string('board')->toString();

        if (! $user instanceof User) {
            return [
                'appNavigation' => collect(),
                'currentModule' => null,
                'currentModuleTabs' => collect(),
            ];
        }

        $staticModules = collect(config('access.navigation', []))
            ->map(function (array $module, string $key) use ($user, $routeName): ?array {
                $modulePermission = $module['permission'] ?? null;

                if ($modulePermission && ! $user->can($modulePermission)) {
                    return null;
                }

                $items = collect($module['items'] ?? [])
                    ->map(function (array $item) use ($user, $routeName): ?array {
                        $permission = $item['permission'] ?? null;
                        $route = $item['route'] ?? null;

                        if (! $route || ! Route::has($route)) {
                            return null;
                        }

                        if ($permission && ! $user->can($permission)) {
                            return null;
                        }

                        $patterns = $item['patterns'] ?? [$route];

                        return [
                            'label' => $item['label'],
                            'route' => $route,
                            'url' => route($route),
                            'active' => $this->routeMatches($patterns, $routeName),
                        ];
                    })
                    ->filter()
                    ->values();

                if ($items->isEmpty()) {
                    return null;
                }

                $modulePatterns = $module['patterns'] ?? [];

                return [
                    'key' => $key,
                    'label' => $module['label'],
                    'url' => $items->first()['url'],
                    'items' => $items,
                    'active' => $items->contains('active', true) || $this->routeMatches($modulePatterns, $routeName),
                ];
            })
            ->filter()
            ->values();

        $staticModuleKeys = collect(config('access.navigation', []))
            ->keys()
            ->all();

        $areaModules = collect(config('access.areas', []))
            ->map(function (string $label, string $key) use ($staticModuleKeys, $user, $routeName, $requestModule, $requestBoard): ?array {
                if (in_array($key, $staticModuleKeys, true)) {
                    return null;
                }

                $boardItems = collect(config('access.boards', []))
                    ->map(function (string $boardLabel, string $boardKey) use ($key, $user, $routeName, $requestModule, $requestBoard): ?array {
                        if ($boardKey === 'documentos') {
                            if (! $this->boardAccess->canViewDocumentsBoard($user, $key)) {
                                return null;
                            }

                            $url = $user->defaultQualityDocumentBoardUrl($key);

                            return [
                                'label' => $boardLabel,
                                'route' => 'quality-documents.library.index',
                                'url' => $url,
                                'active' => str_starts_with((string) $routeName, 'quality-documents.') && $requestModule === $key,
                            ];
                        }

                        if ($boardKey === 'indicadores') {
                            if ($key !== 'operaciones') {
                                return null;
                            }

                            if (! $user->can('operations.view') && ! $user->can('operations.manage') && ! $user->can('operations.capture')) {
                                return null;
                            }

                            return [
                                'label' => $boardLabel,
                                'route' => 'indicadores.dashboard',
                                'url' => $user->defaultIndicadorBoardUrl(),
                                'active' => str_starts_with((string) $routeName, 'indicadores.') && $key === 'operaciones',
                            ];
                        }

                        if ($boardKey === 'matriz_clientes') {
                            if ($key !== 'comercial') {
                                return null;
                            }

                            if (! $user->can('comercial.matriz.view') && ! $user->can('comercial.matriz.manage') && ! $user->can('view.board.comercial.matriz_clientes')) {
                                return null;
                            }

                            return [
                                'label' => $boardLabel,
                                'route' => 'comercial.matriz.clients.index',
                                'url' => route('comercial.matriz.clients.index'),
                                'active' => str_starts_with((string) $routeName, 'comercial.matriz.clients.'),
                            ];
                        }

                        if ($boardKey === 'servicios_comerciales') {
                            if ($key !== 'comercial') {
                                return null;
                            }

                            if (
                                ! $user->can('comercial.matriz.view')
                                && ! $user->can('comercial.matriz.manage')
                                && ! $user->can('view.board.comercial.servicios_comerciales')
                                && ! $user->can('view.board.comercial.matriz_clientes')
                            ) {
                                return null;
                            }

                            return [
                                'label' => $boardLabel,
                                'route' => 'comercial.matriz.services.index',
                                'url' => route('comercial.matriz.services.index'),
                                'active' => str_starts_with((string) $routeName, 'comercial.matriz.services.'),
                            ];
                        }

                        if ($boardKey === 'requisiciones') {
                            if (! $this->requisitionAccess->canViewRequisitionsBoard($user, $key)) {
                                return null;
                            }

                            $url = $user->defaultRequisitionBoardUrl($key);

                            return [
                                'label' => $boardLabel,
                                'route' => 'requisitions.dashboard',
                                'url' => $url,
                                'active' => str_starts_with((string) $routeName, 'requisitions.') && $requestModule === $key,
                            ];
                        }

                        if ($boardKey === 'suministros') {
                            if (! $this->supplyAccess->canViewSupplyBoard($user, $key)) {
                                return null;
                            }

                            $url = $user->defaultSupplyBoardUrl($key);

                            return [
                                'label' => $boardLabel,
                                'route' => 'supplies.index',
                                'url' => $url,
                                'active' => str_starts_with((string) $routeName, 'supplies.') && $requestModule === $key,
                            ];
                        }

                        $permission = "view.board.{$key}.{$boardKey}";

                        if (! $user->can($permission)) {
                            return null;
                        }

                        $url = match (true) {
                            $key === 'comercial' && $boardKey === 'dashboard' => route('comercial.dashboard'),
                            default => route('dashboard', ['module' => $key, 'board' => $boardKey]),
                        };

                        $active = match (true) {
                            $boardKey === 'requisiciones' => str_starts_with((string) $routeName, 'requisitions.') && $requestModule === $key,
                            $boardKey === 'suministros' => str_starts_with((string) $routeName, 'supplies.') && $requestModule === $key,
                            $boardKey === 'indicadores' => str_starts_with((string) $routeName, 'indicadores.') && $key === 'operaciones',
                            $boardKey === 'matriz_clientes' => str_starts_with((string) $routeName, 'comercial.matriz.clients.') && $key === 'comercial',
                            $boardKey === 'servicios_comerciales' => str_starts_with((string) $routeName, 'comercial.matriz.services.') && $key === 'comercial',
                            $key === 'comercial' && $boardKey === 'dashboard' => $routeName === 'comercial.dashboard',
                            default => $routeName === 'dashboard' && $requestBoard === $boardKey && $requestModule === $key,
                        };

                        return [
                            'label' => $boardLabel,
                            'route' => $boardKey === 'requisiciones' ? 'requisitions.dashboard' : ($key === 'comercial' && $boardKey === 'dashboard' ? 'comercial.dashboard' : 'dashboard'),
                            'url' => $url,
                            'active' => $active,
                        ];
                    })
                    ->filter()
                    ->values();

                if ($key === 'comercial'
                    && (
                        $user->can('comercial.matriz.view')
                        || $user->can('comercial.matriz.manage')
                        || $user->can('view.board.comercial.matriz_clientes')
                        || $user->can('view.board.comercial.servicios_comerciales')
                    )
                    && $boardItems->doesntContain('label', config('access.boards.dashboard'))
                ) {
                    $boardItems->prepend([
                        'label' => config('access.boards.dashboard'),
                        'route' => 'comercial.dashboard',
                        'url' => route('comercial.dashboard'),
                        'active' => $routeName === 'comercial.dashboard',
                    ]);
                }

                if ($boardItems->isEmpty()) {
                    return null;
                }

                $url = route('dashboard', ['module' => $key]);
                $active = (
                    $routeName === 'dashboard' && $requestModule === $key
                ) || (
                    str_starts_with((string) $routeName, 'requisitions.') && (string) request()->route('module') === $key
                ) || (
                    str_starts_with((string) $routeName, 'supplies.') && (string) request()->route('module') === $key
                ) || (
                    str_starts_with((string) $routeName, 'quality-documents.') && (string) request()->route('module') === $key
                ) || (
                    str_starts_with((string) $routeName, 'indicadores.') && $key === 'operaciones'
                ) || (
                    (
                        $routeName === 'comercial.dashboard'
                        || str_starts_with((string) $routeName, 'comercial.matriz.clients.')
                        || str_starts_with((string) $routeName, 'comercial.matriz.services.')
                    ) && $key === 'comercial'
                );

                return [
                    'key' => $key,
                    'label' => $label,
                    'url' => $boardItems->first()['url'] ?? $url,
                    'items' => $boardItems,
                    'active' => $active || $boardItems->contains('active', true),
                ];
            })
            ->filter()
            ->values();

        $modules = $staticModules
            ->concat($areaModules)
            ->values();

        $currentModule = $modules->firstWhere('active', true);

        return [
            'appNavigation' => $modules,
            'currentModule' => $currentModule,
            'currentModuleTabs' => collect($currentModule['items'] ?? []),
        ];
    }

    /**
     * @param  array<int, string>  $patterns
     */
    public function routeMatches(array $patterns, ?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    public function resolveRequestModule(?string $routeName): string
    {
        if (str_starts_with((string) $routeName, 'requisitions.')
            || str_starts_with((string) $routeName, 'supplies.')
            || str_starts_with((string) $routeName, 'quality-documents.')) {
            return (string) request()->route('module');
        }

        return request()->string('module')->toString();
    }
}
