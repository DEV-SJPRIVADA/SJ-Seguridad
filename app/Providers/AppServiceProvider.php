<?php

namespace App\Providers;

use App\Models\PersonalRequisition;
use App\Models\QualityDocument;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Policies\PersonalRequisitionPolicy;
use App\Policies\QualityDocumentPolicy;
use App\Policies\SupplyRequestPolicy;
use App\Services\Navigation\NavigationResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
            $view->with(app(NavigationResolver::class)->resolve());
        });
    }
}
