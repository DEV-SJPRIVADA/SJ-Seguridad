<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\StoreCommercialServiceRequest;
use App\Http\Requests\Comercial\UpdateCommercialServiceRequest;
use App\Models\CommercialClient;
use App\Models\CommercialClientType;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommercialServiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $portfolio = $request->string('portfolio')->toString();
        $vigencia = $request->string('vigencia')->toString();

        $today = now()->startOfDay();
        $in30 = now()->startOfDay()->addDays(30);

        $services = CommercialService::query()
            ->with(['client', 'serviceType'])
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('contract_number', 'like', "%{$q}%")
                        ->orWhere('advisor_name', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($clientQuery) use ($q): void {
                            $clientQuery->where('nit', 'like', "%{$q}%")
                                ->orWhere('name', 'like', "%{$q}%");
                        });
                });
            })
            ->when(
                $portfolio !== '' && array_key_exists($portfolio, CommercialService::portfolios()),
                fn ($query) => $query->where('portfolio', $portfolio)
            )
            ->when($vigencia === 'expiring', function ($query) use ($today, $in30): void {
                $query->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS)
                    ->whereNotNull('contract_end')
                    ->whereDate('contract_end', '>=', $today)
                    ->whereDate('contract_end', '<=', $in30);
            })
            ->when($vigencia === 'expired', function ($query) use ($today): void {
                $query->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS)
                    ->whereNotNull('contract_end')
                    ->whereDate('contract_end', '<', $today);
            })
            ->orderByRaw('CASE WHEN portfolio = ? THEN 1 ELSE 0 END', [CommercialService::PORTFOLIO_INACTIVOS])
            ->orderByDesc('contract_end')
            ->orderBy('contract_number')
            ->paginate(20)
            ->withQueryString();

        return view('areas.comercial.matriz-clientes.services.index', [
            'services' => $services,
            'portfolios' => CommercialService::portfolios(),
            'filters' => ['q' => $q, 'portfolio' => $portfolio, 'vigencia' => $vigencia],
            'canManage' => $this->canManage(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeManage();

        $preselectedClientId = (int) (old('commercial_client_id') ?: $request->integer('client') ?: 0) ?: null;

        return view('areas.comercial.matriz-clientes.services.create', [
            'service' => new CommercialService([
                'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
                'commercial_client_id' => $preselectedClientId,
            ]),
            'selectedClient' => $this->resolveSelectedClient($preselectedClientId),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreCommercialServiceRequest $request): RedirectResponse
    {
        $this->authorizeManage();

        CommercialService::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('comercial.matriz.services.index')
            ->with('status', 'Servicio creado y vinculado al cliente.');
    }

    public function edit(CommercialService $service): View
    {
        $this->authorizeManage();
        $service->load('client');

        $selectedClientId = (int) (old('commercial_client_id') ?: $service->commercial_client_id) ?: null;

        return view('areas.comercial.matriz-clientes.services.edit', [
            'service' => $service,
            'selectedClient' => $this->resolveSelectedClient($selectedClientId) ?? $service->client,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCommercialServiceRequest $request, CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();

        $service->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('comercial.matriz.services.index')
            ->with('status', 'Servicio actualizado.');
    }

    public function inactivate(CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();

        $service->update([
            'portfolio' => CommercialService::PORTFOLIO_INACTIVOS,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('comercial.matriz.services.index')
            ->with('status', 'Servicio movido a Inactivos.');
    }

    private function resolveSelectedClient(?int $clientId): ?CommercialClient
    {
        if (! $clientId) {
            return null;
        }

        return CommercialClient::query()->find($clientId, ['id', 'nit', 'name', 'city']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'portfolios' => CommercialService::portfolios(),
            'documentStatuses' => CommercialService::documentStatuses(),
            'documentFields' => CommercialService::documentFields(),
            'sectors' => CommercialSector::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'clientTypes' => CommercialClientType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'serviceTypes' => CommercialServiceType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'clientSearchUrl' => route('comercial.matriz.clients.search'),
        ];
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.view')
            || auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('view.board.comercial.servicios_comerciales')
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
