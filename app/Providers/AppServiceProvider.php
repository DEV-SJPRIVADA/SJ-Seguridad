<?php

namespace App\Providers;

use App\Models\PersonalRequisition;
use App\Models\PurchaseRequest;
use App\Models\QualityDocument;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Policies\PersonalRequisitionPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\QualityDocumentPolicy;
use App\Policies\SupplyRequestPolicy;
use App\Services\Navigation\NavigationResolver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Paginator::defaultView('vendor.pagination.sj');

        Gate::before(function (User $user, string $ability): ?bool {
            if ($ability === 'system.view.audit') {
                return null;
            }

            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        Gate::policy(QualityDocument::class, QualityDocumentPolicy::class);
        Gate::policy(SupplyRequest::class, SupplyRequestPolicy::class);
        Gate::policy(PersonalRequisition::class, PersonalRequisitionPolicy::class);
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);

        Route::bind('supply_request', function (string $value, $route) {
            $query = SupplyRequest::query()->whereKey($value);

            $routeName = (string) $route->getName();
            $isCrossAreaRoute = str_starts_with($routeName, 'supplies.approved.')
                || str_starts_with($routeName, 'purchase-requests.processing.');

            if (! $isCrossAreaRoute) {
                $query->where('area_key', (string) $route->parameter('module'));
            }

            return $query->firstOrFail();
        });

        View::composer(['layouts.app', 'layouts.navigation'], function ($view): void {
            $view->with(app(NavigationResolver::class)->resolve());
        });
    }
}
