<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\CommercialClient;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommercialDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorizeView();

        $filters = [
            'portfolio' => $request->string('portfolio')->toString(),
            'city' => trim($request->string('city')->toString()),
            'year' => (int) $request->input('year', now()->year),
            'month' => $request->input('month') !== null && $request->input('month') !== ''
                ? (int) $request->input('month')
                : null,
        ];

        // Stock actual: solo portafolio / ciudad (no fecha de inicio de contrato).
        $services = $this->stockServicesQuery($filters)
            ->with(['client:id,name,nit,city', 'serviceType:id,name'])
            ->get();

        $today = now()->startOfDay();
        $in30 = now()->startOfDay()->addDays(30);

        $activeServices = $services->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS);
        $inactiveServices = $services->where('portfolio', CommercialService::PORTFOLIO_INACTIVOS);

        $expiringSoon = $activeServices->filter(function (CommercialService $service) use ($today, $in30): bool {
            if (! $service->contract_end instanceof Carbon) {
                return false;
            }

            return $service->contract_end->gte($today) && $service->contract_end->lte($in30);
        });

        $expired = $activeServices->filter(function (CommercialService $service) use ($today): bool {
            return $service->contract_end instanceof Carbon && $service->contract_end->lt($today);
        });

        $totalClients = $this->stockClientsQuery($filters)->count();
        $newClients = $this->newClientsQuery($filters)->count();

        // Tendencia: altas de servicios por mes del año (contract_start), sin filtrar por mes del formulario.
        $trendServices = $this->stockServicesQuery($filters)
            ->when($filters['year'] > 0, fn ($query) => $query->whereYear('contract_start', $filters['year']))
            ->get(['id', 'portfolio', 'contract_start', 'commercial_client_id', 'commercial_service_type_id']);

        $statsByMonth = $trendServices
            ->filter(fn (CommercialService $service) => $service->contract_start instanceof Carbon)
            ->groupBy(fn (CommercialService $service) => (int) $service->contract_start->format('n'))
            ->map->count();

        $statsByPortfolio = $services->groupBy('portfolio')->map->count();

        $statsByCity = $services
            ->groupBy(fn (CommercialService $service) => $service->client?->city ?: 'Sin ciudad')
            ->map->count()
            ->sortDesc()
            ->take(5);

        $statsByServiceType = $services
            ->groupBy(fn (CommercialService $service) => $service->commercial_service_type_id ?: 0)
            ->map->count()
            ->sortDesc()
            ->take(5);

        $serviceTypeNames = CommercialServiceType::query()
            ->whereIn('id', $statsByServiceType->keys()->filter()->all())
            ->pluck('name', 'id');

        $portfolioLabels = CommercialService::portfolios();

        $cities = CommercialClient::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('areas.comercial.dashboard', [
            'filters' => $filters,
            'portfolios' => $portfolioLabels,
            'cities' => $cities,
            'yearOptions' => $this->yearOptions(),
            'stats' => [
                'total_clients' => $totalClients,
                'new_clients' => $newClients,
                'active_services' => $activeServices->count(),
                'expiring_soon' => $expiringSoon->count(),
                'expired' => $expired->count(),
                'inactive_services' => $inactiveServices->count(),
            ],
            'chartData' => [
                'trend' => [
                    'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    'data' => collect(range(1, 12))->map(fn (int $month) => $statsByMonth->get($month, 0))->values(),
                ],
                'portfolio' => [
                    'labels' => collect($portfolioLabels)->values(),
                    'data' => collect($portfolioLabels)->keys()->map(fn (string $key) => $statsByPortfolio->get($key, 0))->values(),
                ],
                'cities' => [
                    'labels' => $statsByCity->keys()->values(),
                    'data' => $statsByCity->values()->values(),
                ],
                'serviceTypes' => [
                    'labels' => $statsByServiceType->keys()->map(function ($id) use ($serviceTypeNames) {
                        if ((int) $id === 0) {
                            return 'Sin tipo';
                        }

                        return $serviceTypeNames[$id] ?? 'Desconocido';
                    })->values(),
                    'data' => $statsByServiceType->values()->values(),
                ],
            ],
        ]);
    }

    /**
     * @param  array{portfolio: string, city: string, year: int, month: int|null}  $filters
     */
    private function stockServicesQuery(array $filters): Builder
    {
        return CommercialService::query()
            ->when(
                $filters['portfolio'] !== '' && array_key_exists($filters['portfolio'], CommercialService::portfolios()),
                fn ($query) => $query->where('portfolio', $filters['portfolio'])
            )
            ->when($filters['city'] !== '', function ($query) use ($filters): void {
                $query->whereHas('client', fn ($clientQuery) => $clientQuery->where('city', $filters['city']));
            });
    }

    /**
     * @param  array{portfolio: string, city: string, year: int, month: int|null}  $filters
     */
    private function stockClientsQuery(array $filters): Builder
    {
        return CommercialClient::query()
            ->when($filters['city'] !== '', fn ($query) => $query->where('city', $filters['city']))
            ->when(
                $filters['portfolio'] !== '' && array_key_exists($filters['portfolio'], CommercialService::portfolios()),
                fn ($query) => $query->whereHas('services', fn ($serviceQuery) => $serviceQuery->where('portfolio', $filters['portfolio']))
            );
    }

    /**
     * Clientes ingresados en el periodo del filtro (año, y mes si aplica).
     *
     * @param  array{portfolio: string, city: string, year: int, month: int|null}  $filters
     */
    private function newClientsQuery(array $filters): Builder
    {
        return $this->stockClientsQuery($filters)
            ->when($filters['year'] > 0, fn ($query) => $query->whereYear('created_at', $filters['year']))
            ->when($filters['month'], fn ($query) => $query->whereMonth('created_at', $filters['month']));
    }

    /**
     * @return Collection<int, int>
     */
    private function yearOptions(): Collection
    {
        $minServiceYear = CommercialService::query()
            ->whereNotNull('contract_start')
            ->min('contract_start');

        $minClientYear = CommercialClient::query()->min('created_at');

        $candidates = collect([
            $minServiceYear ? (int) Carbon::parse($minServiceYear)->year : null,
            $minClientYear ? (int) Carbon::parse($minClientYear)->year : null,
            now()->year,
        ])->filter();

        $startYear = (int) $candidates->min();

        return collect(range(now()->year, $startYear));
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.view')
            || auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('view.board.comercial.matriz_clientes')
            || auth()->user()?->can('view.board.comercial.servicios_comerciales')
            || auth()->user()?->can('view.board.comercial.dashboard')
            || auth()->user()?->can('view.area.comercial')
            || auth()->user()?->can('manage.area.comercial')
            || auth()->user()?->can('manage.users'),
            403
        );
    }
}
