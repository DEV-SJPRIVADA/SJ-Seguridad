<?php

namespace App\Http\Controllers\Comercial;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\StoreCommercialServiceRequest;
use App\Http\Requests\Comercial\UpdateCommercialServiceRequest;
use App\Models\CommercialClient;
use App\Models\CommercialClientType;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use App\Services\Comercial\CommercialAuditLogService;
use App\Support\DisplayDate;
use App\Traits\HasGestionClientesTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialServiceController extends Controller
{
    use HasGestionClientesTabs;

    public function __construct(
        private readonly CommercialAuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $portfolio = $request->string('portfolio')->toString();
        $vigencia = $request->string('vigencia')->toString();
        $status = $this->resolveServiceStatusFilter($request);

        $services = $this->filteredServicesQuery($q, $portfolio, $vigencia, $status)->get();

        return view('areas.comercial.matriz-clientes.services.index', [
            'services' => $services,
            'portfolios' => CommercialService::portfolios(),
            'filters' => ['q' => $q, 'portfolio' => $portfolio, 'vigencia' => $vigencia, 'status' => $status],
            'statusLabels' => self::serviceStatusFilterLabels(),
            'canManage' => $this->canManage(),
            'subTabs' => $this->getGestionClientesSubTabs('servicios'),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $portfolio = $request->string('portfolio')->toString();
        $vigencia = $request->string('vigencia')->toString();
        $status = $this->resolveServiceStatusFilter($request);

        $services = $this->filteredServicesQuery($q, $portfolio, $vigencia, $status)->get();

        $portfolios = CommercialService::portfolios();

        $columns = [
            ['key' => fn ($s) => $s->client?->nit ?? '—', 'label' => 'NIT'],
            ['key' => fn ($s) => $s->client?->name ?? '—', 'label' => 'Cliente'],
            ['key' => 'contract_number', 'label' => 'Contrato'],
            ['key' => fn ($s) => $s->serviceType?->name ?? '—', 'label' => 'Tipo servicio'],
            ['key' => fn ($s) => $portfolios[$s->portfolio] ?? $s->portfolio, 'label' => 'Portafolio'],
            ['key' => 'advisor_name', 'label' => 'Asesor'],
            ['key' => fn ($s) => DisplayDate::date($s->contract_start), 'label' => 'Inicio'],
            ['key' => fn ($s) => DisplayDate::date($s->contract_end), 'label' => 'Fin'],
            ['key' => fn ($s) => $s->serviceEstadoLabel(), 'label' => 'Estado'],
        ];

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'services_excel',
            metadata: $this->serviceExportAuditMetadata($q, $portfolio, $vigencia, $status, $services->count()),
        );

        return (new BaseExport($services, $columns, 'servicios_'.now()->format('Y-m-d').'.xlsx', 'Servicios - '.config('app.name')))->download();
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
            'subTabs' => $this->getGestionClientesSubTabs('servicios'),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreCommercialServiceRequest $request): RedirectResponse
    {
        $this->authorizeManage();

        $service = CommercialService::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->auditLogService->logEvent(
            eventType: 'service',
            action: 'create',
            model: $service,
            metadata: [
                'commercial_client_id' => $service->commercial_client_id,
                'contract_number' => $service->contract_number,
                'portfolio' => $service->portfolio,
            ],
        );

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
            'subTabs' => $this->getGestionClientesSubTabs('servicios'),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCommercialServiceRequest $request, CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();

        $before = $this->serviceAuditSnapshot($service);

        $service->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $service->refresh();
        $after = $this->serviceAuditSnapshot($service);
        [$oldValues, $newValues] = $this->diffAuditFields($before, $after);

        if ($oldValues !== []) {
            $this->auditLogService->logModelChange(
                eventType: 'service',
                action: 'update',
                model: $service,
                before: $oldValues,
                after: $newValues,
            );
        }

        return redirect()
            ->route('comercial.matriz.services.index')
            ->with('status', 'Servicio actualizado.');
    }

    public function inactivate(CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();

        $previousIsActive = (bool) $service->is_active;

        $service->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'service',
            action: 'deactivate',
            model: $service,
            metadata: ['previous_is_active' => $previousIsActive],
        );

        return redirect()
            ->route('comercial.matriz.services.index')
            ->with('status', 'Servicio inactivado.');
    }

    public function activate(CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();

        $previousIsActive = (bool) $service->is_active;

        $service->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'service',
            action: 'activate',
            model: $service,
            metadata: ['previous_is_active' => $previousIsActive],
        );

        return redirect()
            ->back()
            ->with('status', 'Servicio activado.');
    }

    private function resolveSelectedClient(?int $clientId): ?CommercialClient
    {
        if (! $clientId) {
            return null;
        }

        return CommercialClient::query()->find($clientId, ['id', 'nit', 'name', 'city']);
    }

    /**
     * @return Builder<CommercialService>
     */
    private function filteredServicesQuery(string $q, string $portfolio, string $vigencia, string $status = '')
    {
        return CommercialService::query()
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
            ->filterByVigencia($vigencia)
            ->filterByServiceEstado($status)
            ->orderBy('is_active')
            ->orderByDesc('contract_end')
            ->orderBy('contract_number');
    }

    /**
     * @return array<string, string>
     */
    private static function serviceStatusFilterLabels(): array
    {
        return [
            'activo' => CommercialService::ESTADO_ACTIVO,
            'por_vencer' => CommercialService::ESTADO_POR_VENCER,
            'vencido' => CommercialService::ESTADO_VENCIDO,
            'inactivo' => CommercialService::ESTADO_INACTIVO,
        ];
    }

    private function resolveServiceStatusFilter(Request $request): string
    {
        $status = trim($request->string('status')->toString());

        return array_key_exists($status, self::serviceStatusFilterLabels()) ? $status : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'portfolios' => CommercialService::portfolios(),
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

    /**
     * @return array<string, mixed>
     */
    private function serviceAuditSnapshot(CommercialService $service): array
    {
        return [
            'contract_number' => $service->contract_number,
            'portfolio' => $service->portfolio,
            'contract_start' => $service->contract_start?->toDateString(),
            'contract_end' => $service->contract_end?->toDateString(),
            'advisor_name' => $service->advisor_name,
            'is_active' => (bool) $service->is_active,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function diffAuditFields(array $before, array $after): array
    {
        $oldValues = [];
        $newValues = [];

        foreach ($before as $field => $beforeValue) {
            $afterValue = $after[$field] ?? null;

            if ($beforeValue !== $afterValue) {
                $oldValues[$field] = $beforeValue;
                $newValues[$field] = $afterValue;
            }
        }

        return [$oldValues, $newValues];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceExportAuditMetadata(
        string $q,
        string $portfolio,
        string $vigencia,
        string $status,
        int $rowCount,
    ): array {
        $filters = array_filter([
            'q' => $q !== '' ? $q : null,
            'portfolio' => $portfolio !== '' ? $portfolio : null,
            'vigencia' => $vigencia !== '' ? $vigencia : null,
            'status' => $status !== '' ? $status : null,
        ], fn ($value) => $value !== null);

        return [
            'row_count' => $rowCount,
            'filters' => $filters,
        ];
    }
}
