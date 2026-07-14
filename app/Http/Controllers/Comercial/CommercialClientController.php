<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\StoreCommercialClientRequest;
use App\Http\Requests\Comercial\UpdateCommercialClientRequest;
use App\Models\CommercialClient;
use App\Models\CommercialService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommercialClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $city = trim($request->string('city')->toString());

        $clients = CommercialClient::query()
            ->withCount([
                'services',
                'activeServices',
            ])
            ->with([
                'services' => fn ($query) => $query
                    ->select([
                        'id',
                        'commercial_client_id',
                        'commercial_service_type_id',
                        'portfolio',
                        'contract_start',
                        'contract_end',
                    ])
                    ->with('serviceType:id,name'),
            ])
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('nit', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('legal_rep_name', 'like', "%{$q}%");
                });
            })
            ->when($city !== '', fn ($query) => $query->where('city', 'like', "%{$city}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $clients->getCollection()->transform(function (CommercialClient $client): CommercialClient {
            $activeServices = $client->services
                ->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS);
            $servicesForDates = $activeServices->isNotEmpty() ? $activeServices : $client->services;

            $client->setAttribute(
                'service_type_labels',
                $client->services
                    ->pluck('serviceType.name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all()
            );

            $portfolioLabels = CommercialService::portfolios();
            $client->setAttribute(
                'portfolio_labels',
                $client->services
                    ->pluck('portfolio')
                    ->filter()
                    ->unique()
                    ->map(fn (string $portfolio) => $portfolioLabels[$portfolio] ?? $portfolio)
                    ->sort()
                    ->values()
                    ->all()
            );
            $client->setAttribute(
                'contract_start_display',
                optional($servicesForDates->pluck('contract_start')->filter()->sort()->first())->format('Y-m-d')
            );
            $client->setAttribute(
                'contract_end_display',
                optional($servicesForDates->pluck('contract_end')->filter()->sort()->last())->format('Y-m-d')
            );

            return $client;
        });

        return view('areas.comercial.matriz-clientes.clients.index', [
            'clients' => $clients,
            'filters' => ['q' => $q, 'city' => $city],
            'canManage' => $this->canManage(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $digits = preg_replace('/\D+/', '', $q) ?: '';

        $clients = CommercialClient::query()
            ->where(function ($query) use ($q, $digits): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('nit', 'like', "%{$q}%");

                if ($digits !== '' && $digits !== $q) {
                    $query->orWhere('nit', 'like', "%{$digits}%");
                }
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'nit', 'name', 'city']);

        return response()->json([
            'data' => $clients->map(fn (CommercialClient $client) => [
                'id' => $client->id,
                'nit' => $client->nit,
                'name' => $client->name,
                'city' => $client->city,
                'label' => "{$client->name} ({$client->nit})",
            ])->values(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeManage();

        return view('areas.comercial.matriz-clientes.clients.create', [
            'client' => new CommercialClient(),
        ]);
    }

    public function store(StoreCommercialClientRequest $request): RedirectResponse
    {
        $this->authorizeManage();

        $client = CommercialClient::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('comercial.matriz.clients.show', $client)
            ->with('status', 'Cliente creado.');
    }

    public function show(Request $request, CommercialClient $client): View
    {
        $this->authorizeView();

        $portfolio = $request->string('portfolio')->toString();

        $services = $client->services()
            ->with(['sector', 'clientType', 'serviceType'])
            ->when(
                $portfolio !== '' && array_key_exists($portfolio, CommercialService::portfolios()),
                fn ($query) => $query->where('portfolio', $portfolio)
            )
            ->orderByRaw("CASE WHEN portfolio = ? THEN 1 ELSE 0 END", [CommercialService::PORTFOLIO_INACTIVOS])
            ->orderByDesc('contract_end')
            ->orderBy('contract_number')
            ->get();

        return view('areas.comercial.matriz-clientes.clients.show', [
            'client' => $client,
            'services' => $services,
            'portfolios' => CommercialService::portfolios(),
            'filters' => ['portfolio' => $portfolio],
            'canManage' => $this->canManage(),
        ]);
    }

    public function edit(CommercialClient $client): View
    {
        $this->authorizeManage();

        return view('areas.comercial.matriz-clientes.clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateCommercialClientRequest $request, CommercialClient $client): RedirectResponse
    {
        $this->authorizeManage();

        $client->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('comercial.matriz.clients.show', $client)
            ->with('status', 'Cliente actualizado.');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.view')
            || auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('view.board.comercial.matriz_clientes')
            || auth()->user()?->can('manage.users'),
            403
        );
    }

    private function authorizeManage(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('manage.users'),
            403
        );
    }

    private function canManage(): bool
    {
        return (bool) (auth()->user()?->can('comercial.matriz.manage') || auth()->user()?->can('manage.users'));
    }
}
