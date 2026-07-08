<?php

namespace App\Providers;

use App\Models\QualityDocument;
use App\Models\PersonalRequisition;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Policies\PersonalRequisitionPolicy;
use App\Policies\QualityDocumentPolicy;
use App\Policies\SupplyRequestPolicy;
use App\Services\Access\BoardAccessService;
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

        Gate::policy(QualityDocument::class, QualityDocumentPolicy::class);
        Gate::policy(SupplyRequest::class, SupplyRequestPolicy::class);
        Gate::policy(PersonalRequisition::class, PersonalRequisitionPolicy::class);

        Route::bind('supply_request', function (string $value, $route) {
            $query = SupplyRequest::query()->whereKey($value);

            $routeName = (string) $route->getName();
            $isCrossAreaApprovedRoute = str_starts_with($routeName, 'supplies.approved.');

            if (! $isCrossAreaApprovedRoute) {
                $query->where('area_key', (string) $route->parameter('module'));
            }

            return $query->firstOrFail();
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
                        if ($boardKey === 'documentos') {
                            if (! $this->canViewDocumentsBoard($user, $key)) {
                                return null;
                            }

                            $url = $user->defaultQualityDocumentBoardUrl($key);
                            $requestModule = $this->resolveRequestModule($routeName);

                            return [
                                'label' => $boardLabel,
                                'route' => 'quality-documents.library.index',
                                'url' => $url,
                                'active' => str_starts_with((string) $routeName, 'quality-documents.') && $requestModule === $key,
                            ];
                        }

                        $permission = "view.board.{$key}.{$boardKey}";

                        if (! $user->can($permission)) {
                            return null;
                        }

                        $url = match($boardKey) {
                            'requisiciones' => $user->defaultRequisitionBoardUrl($key),
                            'suministros' => $user->defaultSupplyBoardUrl($key),
                            default => route('dashboard', ['module' => $key, 'board' => $boardKey]),
                        };

                        $requestModule = $this->resolveRequestModule($routeName);
                        $requestBoard = request()->string('board')->toString();

                        $active = match($boardKey) {
                            'requisiciones' => str_starts_with((string) $routeName, 'requisitions.') && $requestModule === $key,
                            'suministros' => str_starts_with((string) $routeName, 'supplies.') && $requestModule === $key,
                            default => $routeName === 'dashboard' && $requestBoard === $boardKey && $requestModule === $key,
                        };

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
                ) || (
                    str_starts_with((string) $routeName, 'supplies.') && (string) request()->route('module') === $key
                ) || (
                    str_starts_with((string) $routeName, 'quality-documents.') && (string) request()->route('module') === $key
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

    private function canViewDocumentsBoard(User $user, string $areaKey): bool
    {
        return app(BoardAccessService::class)->canViewDocumentsBoard($user, $areaKey);
    }

    private function resolveRequestModule(?string $routeName): string
    {
        if (str_starts_with((string) $routeName, 'requisitions.')
            || str_starts_with((string) $routeName, 'supplies.')
            || str_starts_with((string) $routeName, 'quality-documents.')) {
            return (string) request()->route('module');
        }

        return request()->string('module')->toString();
    }
}
