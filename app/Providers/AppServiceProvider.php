<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        View::composer(['layouts.app', 'layouts.navigation'], function ($view): void {
            $view->with($this->resolveNavigation());
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveNavigation(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [
                'appNavigation' => collect(),
                'currentModule' => null,
                'currentModuleTabs' => collect(),
            ];
        }

        $routeName = request()->route()?->getName();

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
            ->map(function (string $label, string $key) use ($staticModuleKeys, $user, $routeName): ?array {
                if (in_array($key, $staticModuleKeys, true)) {
                    return null;
                }

                $canViewArea = $user->can("view.area.{$key}") || $user->can("manage.area.{$key}");

                $boardItems = collect(config('access.boards', []))
                    ->map(function (string $boardLabel, string $boardKey) use ($key, $user, $routeName): ?array {
                        $permission = "view.board.{$key}.{$boardKey}";

                        if (! $user->can($permission)) {
                            return null;
                        }

                        $url = $boardKey === 'requisiciones'
                            ? route('requisitions.dashboard', ['module' => $key])
                            : route('dashboard', ['module' => $key, 'board' => $boardKey]);

                        $requestModule = str_starts_with((string) $routeName, 'requisitions.')
                            ? (string) request()->route('module')
                            : request()->string('module')->toString();
                        $requestBoard = request()->string('board')->toString();
                        $active = $boardKey === 'requisiciones'
                            ? str_starts_with((string) $routeName, 'requisitions.') && $requestModule === $key
                            : $routeName === 'dashboard' && $requestBoard === $boardKey && $requestModule === $key;

                        return [
                            'label' => $boardLabel,
                            'route' => $boardKey === 'requisiciones' ? 'requisitions.dashboard' : 'dashboard',
                            'url' => $url,
                            'active' => $active,
                        ];
                    })
                    ->filter()
                    ->values();

                if ($canViewArea && $boardItems->doesntContain('label', config('access.boards.dashboard'))) {
                    $dashboardUrl = route('dashboard', ['module' => $key, 'board' => 'dashboard']);

                    $boardItems->prepend([
                        'label' => config('access.boards.dashboard'),
                        'route' => 'dashboard',
                        'url' => $dashboardUrl,
                        'active' => $routeName === 'dashboard'
                            && request()->string('module')->toString() === $key
                            && request()->string('board')->toString() === 'dashboard',
                    ]);
                }

                if (! $canViewArea && $boardItems->isEmpty()) {
                    return null;
                }

                $url = route('dashboard', ['module' => $key]);
                $active = (
                    $routeName === 'dashboard' && request()->string('module')->toString() === $key
                ) || (
                    str_starts_with((string) $routeName, 'requisitions.') && (string) request()->route('module') === $key
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
    private function routeMatches(array $patterns, ?string $routeName): bool
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
}
