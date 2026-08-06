<?php

namespace App\Http\Controllers\Comercial;

use App\Exports\BaseExport;
use App\Exports\CommercialMatrixImportTemplateExport;
use App\Http\Controllers\Concerns\HandlesImportFailureReports;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\ImportCommercialMatrixRequest;
use App\Http\Requests\Comercial\StoreCommercialClientRequest;
use App\Http\Requests\Comercial\UpdateCommercialClientRequest;
use App\Models\CommercialClient;
use App\Models\CommercialService;
use App\Services\Comercial\CommercialMatrixImportService;
use App\Support\DisplayDate;
use App\Traits\HasGestionClientesTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialClientController extends Controller
{
    use HandlesImportFailureReports;
    use HasGestionClientesTabs;

    public function __construct(
        private readonly CommercialMatrixImportTemplateExport $importTemplateExport,
        private readonly CommercialMatrixImportService $importService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $city = trim($request->string('city')->toString());
        $status = $this->resolveClientStatusFilter($request);

        $clients = $this->clientListQuery($q, $city, $status)
            ->get();

        $clients->transform(function (CommercialClient $client): CommercialClient {
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
                DisplayDate::date($servicesForDates->pluck('contract_start')->filter()->sort()->first())
            );
            $client->setAttribute(
                'contract_end_display',
                DisplayDate::date($servicesForDates->pluck('contract_end')->filter()->sort()->last())
            );

            return $client;
        });

        return view('areas.comercial.matriz-clientes.clients.index', [
            'clients' => $clients,
            'filters' => ['q' => $q, 'city' => $city, 'status' => $status],
            'statusLabels' => self::clientStatusFilterLabels(),
            'canManage' => $this->canManage(),
            'subTabs' => $this->getGestionClientesSubTabs('clientes'),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $city = trim($request->string('city')->toString());
        $status = $this->resolveClientStatusFilter($request);

        $clients = $this->clientListQuery($q, $city, $status)
            ->get();

        $portfolioLabels = CommercialService::portfolios();

        $clients->transform(function (CommercialClient $client) use ($portfolioLabels): CommercialClient {
            $activeServices = $client->services
                ->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS);
            $servicesForDates = $activeServices->isNotEmpty() ? $activeServices : $client->services;

            $client->setAttribute('service_type_labels', $client->services
                ->pluck('serviceType.name')->filter()->unique()->sort()->values()->all());

            $client->setAttribute('portfolio_labels', $client->services
                ->pluck('portfolio')->filter()->unique()
                ->map(fn (string $p) => $portfolioLabels[$p] ?? $p)->sort()->values()->all());

            $client->setAttribute('contract_start_display',
                DisplayDate::date($servicesForDates->pluck('contract_start')->filter()->sort()->first()));
            $client->setAttribute('contract_end_display',
                DisplayDate::date($servicesForDates->pluck('contract_end')->filter()->sort()->last()));

            return $client;
        });

        $columns = [
            ['key' => 'nit', 'label' => 'NIT'],
            ['key' => 'name', 'label' => 'Cliente'],
            ['key' => 'city', 'label' => 'Ciudad'],
            ['key' => fn ($c) => implode(', ', $c->portfolio_labels ?? []), 'label' => 'Portafolios'],
            ['key' => fn ($c) => implode(', ', $c->service_type_labels ?? []), 'label' => 'Tipos de servicio'],
            ['key' => 'contract_start_display', 'label' => 'Inicio contrato'],
            ['key' => 'contract_end_display', 'label' => 'Fin contrato'],
            ['key' => 'services_count', 'label' => 'Servicios'],
            ['key' => fn ($c) => ($c->active_operational_services_count ?? 0) > 0 ? 'Activo' : 'Inactivo', 'label' => 'Estado'],
        ];

        return (new BaseExport($clients, $columns, 'clientes_'.now()->format('Y-m-d').'.xlsx', 'Clientes - SJ Seguridad'))->download();
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
            'client' => new CommercialClient,
            'subTabs' => $this->getGestionClientesSubTabs('clientes'),
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
            ->orderByRaw('CASE WHEN portfolio = ? THEN 1 ELSE 0 END', [CommercialService::PORTFOLIO_INACTIVOS])
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
            'subTabs' => $this->getGestionClientesSubTabs('clientes'),
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

    public function importTemplate(): StreamedResponse
    {
        $this->authorizeManage();

        return $this->importTemplateExport->download();
    }

    public function exportImportTemplate(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorizeManage();

        $q = trim($request->string('q')->toString());
        $city = trim($request->string('city')->toString());
        $status = $this->resolveClientStatusFilter($request);

        $services = $this->serviceImportQuery($q, $city, $status)->get();

        if ($services->isEmpty()) {
            return redirect()
                ->route('comercial.matriz.clients.index', $request->only(['q', 'city', 'status']))
                ->withErrors(['export' => 'No hay servicios para exportar con los filtros seleccionados.']);
        }

        return $this->importTemplateExport->downloadWithData(
            $services,
            'plantilla_importacion_matriz_comercial_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function import(ImportCommercialMatrixRequest $request): RedirectResponse
    {
        $this->authorizeManage();

        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        $message = sprintf(
            'Importacion finalizada: %d clientes nuevos, %d clientes actualizados, %d servicios nuevos, %d servicios actualizados.',
            $stats['clients_created'],
            $stats['clients_updated'],
            $stats['services_created'],
            $stats['services_updated']
        );

        if ($stats['skipped'] > 0) {
            $message .= sprintf(' %d filas con error u omision.', $stats['skipped']);
        }

        if ($stats['empty_rows'] > 0) {
            $message .= sprintf(' %d filas vacias ignoradas.', $stats['empty_rows']);
        }

        $importResult = $this->buildImportResultPayload(
            $request->user(),
            $stats,
            'commercial_matrix',
            'Matriz comercial',
            [
                'Clientes nuevos' => $stats['clients_created'],
                'Clientes actualizados' => $stats['clients_updated'],
                'Servicios nuevos' => $stats['services_created'],
                'Servicios actualizados' => $stats['services_updated'],
                'Filas omitidas o con error' => $stats['skipped'],
                'Filas vacias' => $stats['empty_rows'],
            ],
            array_keys(config('commercial_matrix.import_columns', [])),
            'reporte_importacion_matriz_comercial',
        );

        return redirect()
            ->route('comercial.matriz.clients.index', $request->only(['q', 'city', 'status']))
            ->with('status', $message)
            ->with('import_result', $importResult);
    }

    public function downloadImportReport(Request $request, string $token): StreamedResponse
    {
        $this->authorizeManage();

        return $this->downloadImportFailureReport($request->user(), $token, 'commercial_matrix');
    }

    /**
     * @return array<string, string>
     */
    private static function clientStatusFilterLabels(): array
    {
        return [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
        ];
    }

    private function resolveClientStatusFilter(Request $request): string
    {
        $status = trim($request->string('status')->toString());

        return array_key_exists($status, self::clientStatusFilterLabels()) ? $status : '';
    }

    /**
     * @return Builder<CommercialClient>
     */
    private function clientListQuery(string $q, string $city, string $status): Builder
    {
        return CommercialClient::query()
            ->withCount([
                'services',
                'activeOperationalServices',
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
            ->when($status === 'active', fn ($query) => $query->whereHas('activeOperationalServices'))
            ->when($status === 'inactive', fn ($query) => $query->whereDoesntHave('activeOperationalServices'))
            ->orderBy('name');
    }

    /**
     * @return Builder<CommercialService>
     */
    private function serviceImportQuery(string $q, string $city, string $status): Builder
    {
        return CommercialService::query()
            ->with([
                'client.documentItems',
                'sector:id,name',
                'clientType:id,name',
                'serviceType:id,name',
            ])
            ->whereHas('client', function (Builder $query) use ($q, $city, $status): void {
                $query
                    ->when($q !== '', function (Builder $inner) use ($q): void {
                        $inner->where(function (Builder $search) use ($q): void {
                            $search->where('nit', 'like', "%{$q}%")
                                ->orWhere('name', 'like', "%{$q}%")
                                ->orWhere('legal_rep_name', 'like', "%{$q}%");
                        });
                    })
                    ->when($city !== '', fn (Builder $inner) => $inner->where('city', 'like', "%{$city}%"))
                    ->when($status === 'active', fn (Builder $inner) => $inner->whereHas('activeOperationalServices'))
                    ->when($status === 'inactive', fn (Builder $inner) => $inner->whereDoesntHave('activeOperationalServices'));
            })
            ->orderBy('commercial_client_id')
            ->orderBy('portfolio')
            ->orderBy('contract_number');
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
