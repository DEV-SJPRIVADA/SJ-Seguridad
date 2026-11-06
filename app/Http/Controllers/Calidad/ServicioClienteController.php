<?php

namespace App\Http\Controllers\Calidad;

use App\Exports\ServicioClienteFoGi22Export;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calidad\ServicioCliente\StoreServicioClienteAdjuntosRequest;
use App\Http\Requests\Calidad\ServicioCliente\StoreServicioClienteCasoRequest;
use App\Http\Requests\Calidad\ServicioCliente\StoreServicioClienteCatalogItemRequest;
use App\Http\Requests\Calidad\ServicioCliente\StoreServicioClienteObservacionRequest;
use App\Http\Requests\Calidad\ServicioCliente\UpdateServicioClienteCasoRequest;
use App\Http\Requests\Calidad\ServicioCliente\UpdateServicioClienteCatalogItemRequest;
use App\Models\CommercialClient;
use App\Models\RequisitionCity;
use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCasoAdjunto;
use App\Models\ServicioClienteCasoObservacion;
use App\Models\ServicioClienteCategoria;
use App\Models\ServicioClienteFuente;
use App\Models\ServicioClienteResponsable;
use App\Models\ServicioClienteTipologia;
use App\Services\Access\ServicioClienteAccessService;
use App\Services\Calidad\ServicioClienteAdjuntoService;
use App\Services\Calidad\ServicioClienteAuditLogService;
use App\Services\Calidad\ServicioClienteCasoService;
use App\Services\Calidad\ServicioClienteCatalogService;
use App\Services\Calidad\ServicioClienteDashboardService;
use App\Services\Calidad\ServicioClienteDatatableService;
use App\Support\ExportRowLimit;
use App\Traits\HasServicioClienteTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioClienteController extends Controller
{
    use HasServicioClienteTabs;

    public function __construct(
        private readonly ServicioClienteAccessService $access,
        private readonly ServicioClienteDatatableService $datatable,
        private readonly ServicioClienteDashboardService $dashboard,
        private readonly ServicioClienteCasoService $casos,
        private readonly ServicioClienteCatalogService $catalogs,
        private readonly ServicioClienteAdjuntoService $adjuntos,
        private readonly ServicioClienteAuditLogService $audit,
        private readonly ServicioClienteFoGi22Export $export,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->access->canViewBoard($user), 403);

        $tabs = $this->access->visibleTabsFor($user);
        $firstTab = $tabs[0] ?? null;
        abort_unless($firstTab !== null, 403);

        $route = match ($firstTab) {
            'fo_gi_22' => 'calidad.servicio-cliente.fo-gi-22.index',
            'catalogos' => 'calidad.servicio-cliente.catalogos',
            default => 'calidad.servicio-cliente.dashboard',
        };

        return redirect()->route($route, $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->access->canViewDashboard(auth()->user()), 403);

        $filters = $this->datatable->filtersFromRequest($request);
        $metrics = $this->dashboard->metrics($filters);

        return view('areas.calidad.servicio_cliente.dashboard', [
            'subTabs' => $this->getServicioClienteSubTabs('dashboard'),
            'filters' => $filters,
            'metrics' => $metrics,
            'options' => $this->filterOptions(),
            'kpiLinks' => $this->foGi22KpiLinks($filters),
        ]);
    }

    public function foGi22(Request $request): View
    {
        abort_unless($this->access->canViewFoGi22(auth()->user()), 403);

        $filters = $this->datatable->listFiltersFromRequest($request);
        $activeQuery = $this->activeQuery($filters);
        $canEdit = $this->access->canEditFoGi22(auth()->user());

        return view('areas.calidad.servicio_cliente.fo-gi-22', [
            'subTabs' => $this->getServicioClienteSubTabs('fo_gi_22'),
            'filters' => $filters,
            'canEdit' => $canEdit,
            'options' => $this->filterOptions(),
            'datatableUrl' => route('calidad.servicio-cliente.fo-gi-22.datatable', $activeQuery),
            'exportUrl' => route('calidad.servicio-cliente.fo-gi-22.export', $activeQuery),
            'historialUrl' => route('calidad.servicio-cliente.fo-gi-22.historial'),
        ]);
    }

    public function historial(): JsonResponse
    {
        abort_unless($this->access->canViewFoGi22(auth()->user()), 403);

        return response()->json([
            'data' => $this->audit->history(),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->access->canViewFoGi22(auth()->user()), 403);

        return $this->datatable->respond(
            $request,
            $this->datatable->listFiltersFromRequest($request),
            $this->access->canEditFoGi22(auth()->user()),
        );
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        abort_unless($this->access->canViewFoGi22(auth()->user()), 403);

        $filters = $this->datatable->listFiltersFromRequest($request);
        $query = $this->datatable->filteredQuery($filters, ordered: false);
        $maxRows = max(1, (int) config('servicio_cliente.export.max_rows', 10000));
        $rowCount = ExportRowLimit::count($query);

        if ($rowCount > $maxRows) {
            return redirect()
                ->route('calidad.servicio-cliente.fo-gi-22.index', $this->activeQuery($filters))
                ->with('error', ExportRowLimit::rejectionMessage($maxRows, $rowCount));
        }

        $casos = (clone $query)
            ->with(['tipologia', 'fuente', 'categoria', 'responsable', 'ciudad'])
            ->orderBy('consecutivo')
            ->get();

        $observaciones = ServicioClienteCasoObservacion::query()
            ->with(['caso:id,consecutivo', 'author:id,name'])
            ->whereIn('caso_id', $casos->pluck('id'))
            ->orderBy('caso_id')
            ->orderBy('id')
            ->get();

        $this->audit->logEvent(
            eventType: 'export',
            action: 'fo_gi_22_excel',
            metadata: [
                'row_count' => $rowCount,
                'filters' => $this->activeQuery($filters),
            ],
            userId: (int) auth()->id(),
        );

        return $this->export->download($casos, $observaciones);
    }

    public function create(): View
    {
        abort_unless($this->access->canEditFoGi22(auth()->user()), 403);

        return view('areas.calidad.servicio_cliente.form', [
            'subTabs' => $this->getServicioClienteSubTabs('fo_gi_22'),
            'caso' => null,
            'canEdit' => true,
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreServicioClienteCasoRequest $request): RedirectResponse
    {
        $caso = $this->casos->create(
            $request->validated(),
            $this->uploadedFiles($request),
            (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.index')
            ->with('status', 'Caso N° '.$caso->consecutivo.' creado.');
    }

    public function show(ServicioClienteCaso $caso): View
    {
        abort_unless($this->access->canViewFoGi22(auth()->user()), 403);

        $caso->load([
            'tipologia',
            'fuente',
            'categoria',
            'responsable',
            'ciudad',
            'cliente',
            'observaciones.author',
            'adjuntos.uploader',
        ]);

        return view('areas.calidad.servicio_cliente.form', [
            'subTabs' => $this->getServicioClienteSubTabs('fo_gi_22'),
            'caso' => $caso,
            'canEdit' => $this->access->canEditFoGi22(auth()->user()),
            'options' => $this->formOptions($caso),
        ]);
    }

    public function update(UpdateServicioClienteCasoRequest $request, ServicioClienteCaso $caso): RedirectResponse
    {
        $this->casos->update(
            $caso,
            $request->validated(),
            $this->uploadedFiles($request),
            (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
            ->with('status', 'Caso N° '.$caso->consecutivo.' actualizado.');
    }

    public function destroy(ServicioClienteCaso $caso): RedirectResponse
    {
        abort_unless($this->access->canEditFoGi22(auth()->user()), 403);

        $consecutivo = $caso->consecutivo;
        $this->casos->delete($caso, (int) auth()->id());

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.index')
            ->with('status', 'Caso N° '.$consecutivo.' eliminado.');
    }

    public function storeObservacion(StoreServicioClienteObservacionRequest $request, ServicioClienteCaso $caso): RedirectResponse
    {
        $this->casos->addObservacion($caso, $request->string('texto')->toString(), (int) auth()->id());

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
            ->with('status', 'Observación agregada.')
            ->withFragment('observaciones');
    }

    public function updateObservacion(StoreServicioClienteObservacionRequest $request, ServicioClienteCaso $caso, ServicioClienteCasoObservacion $observacion): RedirectResponse
    {
        abort_unless($observacion->caso_id === $caso->id, 404);

        $this->casos->updateObservacion($observacion, $request->string('texto')->toString(), (int) auth()->id());

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
            ->with('status', 'Observación actualizada.')
            ->withFragment('observaciones');
    }

    public function destroyObservacion(ServicioClienteCaso $caso, ServicioClienteCasoObservacion $observacion): RedirectResponse
    {
        abort_unless($this->access->canEditFoGi22(auth()->user()), 403);
        abort_unless($observacion->caso_id === $caso->id, 404);

        $this->casos->deleteObservacion($observacion, (int) auth()->id());

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
            ->with('status', 'Observación eliminada.')
            ->withFragment('observaciones');
    }

    public function storeAdjuntos(StoreServicioClienteAdjuntosRequest $request, ServicioClienteCaso $caso): RedirectResponse
    {
        $stored = $this->adjuntos->storeMany($caso, $this->uploadedFiles($request), (int) auth()->id());

        $this->audit->logEvent(
            eventType: 'fo_gi_22_adjunto',
            action: 'create',
            metadata: [
                'caso_id' => $caso->id,
                'consecutivo' => $caso->consecutivo,
                'files' => array_map(fn (ServicioClienteCasoAdjunto $adjunto): string => $adjunto->original_name, $stored),
            ],
            model: $caso,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
            ->with('status', 'Archivos adjuntados.')
            ->withFragment('adjuntos');
    }

    public function downloadAdjunto(ServicioClienteCaso $caso, ServicioClienteCasoAdjunto $adjunto): StreamedResponse
    {
        abort_unless($this->access->canViewFoGi22(auth()->user()), 403);
        abort_unless($adjunto->caso_id === $caso->id, 404);

        $disk = (string) config('servicio_cliente.adjuntos.disk', 'local');
        abort_unless(Storage::disk($disk)->exists($adjunto->stored_path), 404);

        return Storage::disk($disk)->download($adjunto->stored_path, $adjunto->original_name);
    }

    public function destroyAdjunto(ServicioClienteCaso $caso, ServicioClienteCasoAdjunto $adjunto): RedirectResponse
    {
        abort_unless($this->access->canEditFoGi22(auth()->user()), 403);
        abort_unless($adjunto->caso_id === $caso->id, 404);

        try {
            $this->casos->assertCanRemoveAttachment($caso);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
                ->withErrors($exception->errors())
                ->withFragment('adjuntos');
        }

        $metadata = [
            'caso_id' => $caso->id,
            'consecutivo' => $caso->consecutivo,
            'adjunto_id' => $adjunto->id,
            'original_name' => $adjunto->original_name,
            'stored_path' => $adjunto->stored_path,
        ];

        $this->adjuntos->delete($adjunto);

        $this->audit->logEvent(
            eventType: 'fo_gi_22_adjunto',
            action: 'delete',
            metadata: $metadata,
            model: $caso,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.fo-gi-22.show', $caso)
            ->with('status', 'Adjunto eliminado.')
            ->withFragment('adjuntos');
    }

    public function catalogos(Request $request): View
    {
        abort_unless($this->access->canEditParameters(auth()->user()), 403);

        return view('areas.calidad.servicio_cliente.catalogos', [
            'subTabs' => $this->getServicioClienteSubTabs('catalogos'),
            'catalogs' => $this->catalogs->catalogsForAdmin(),
            'labels' => collect($this->catalogs->managedTypes())
                ->mapWithKeys(fn ($class, string $type): array => [$type => $this->catalogs->labelFor($type)])
                ->all(),
            'activeCatalog' => $request->string('catalog')->toString(),
        ]);
    }

    public function storeCatalog(StoreServicioClienteCatalogItemRequest $request, string $type): RedirectResponse
    {
        abort_unless($this->catalogs->isManagedType($type), 404);

        $class = $this->catalogs->modelClassFor($type);
        $code = trim($request->string('code')->toString());
        if ($code === '') {
            $code = $this->catalogs->uniqueCode($type, $request->string('name')->toString());
        }

        $item = $class::query()->create([
            'code' => $code,
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $this->audit->logEvent(
            eventType: 'servicio_cliente_catalog',
            action: 'create',
            metadata: ['catalog_type' => $type, 'code' => $item->code, 'catalog_item_id' => $item->id],
            model: $item,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.catalogos', ['catalog' => $type])
            ->with('status', 'Catálogo actualizado.');
    }

    public function updateCatalog(UpdateServicioClienteCatalogItemRequest $request, string $type, int $item): RedirectResponse
    {
        abort_unless($this->catalogs->isManagedType($type), 404);

        $model = $this->catalogs->findItemOrFail($type, $item);
        $before = $model->only(['code', 'name', 'is_active', 'sort_order']);
        // El formulario solo edita el nombre visible. El código interno se conserva.
        $code = trim($request->string('code')->toString());
        if ($code === '') {
            $code = (string) $model->code;
        }

        $model->update([
            'code' => $code,
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', $model->sort_order ?? 0),
        ]);

        $this->audit->logModelChange(
            eventType: 'servicio_cliente_catalog',
            action: 'update',
            model: $model,
            before: $before,
            after: $model->only(array_keys($before)),
            metadata: ['catalog_type' => $type, 'code' => $model->code],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.catalogos', ['catalog' => $type])
            ->with('status', 'Catálogo actualizado.');
    }

    public function destroyCatalog(string $type, int $item): RedirectResponse
    {
        abort_unless($this->access->canEditParameters(auth()->user()), 403);
        abort_unless($this->catalogs->isManagedType($type), 404);

        $model = $this->catalogs->findItemOrFail($type, $item);

        try {
            $this->catalogs->assertDeletable($type, $model);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('calidad.servicio-cliente.catalogos', ['catalog' => $type])
                ->withErrors($exception->errors());
        }

        $metadata = [
            'catalog_type' => $type,
            'code' => $model->code,
            'name' => $model->name,
            'catalog_item_id' => $model->id,
        ];
        $model->delete();

        $this->audit->logEvent(
            eventType: 'servicio_cliente_catalog',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('calidad.servicio-cliente.catalogos', ['catalog' => $type])
            ->with('status', 'Valor eliminado del catálogo.');
    }

    /**
     * Enlaces de los KPI al listado, conservando los filtros del dashboard.
     *
     * @param  array<string, mixed>  $filters
     * @return array{abiertos: string, en_proceso: string, cerrados: string, vencidos: string}
     */
    private function foGi22KpiLinks(array $filters): array
    {
        $eficacia = (string) ($filters['eficacia'] ?? '');

        return [
            'abiertos' => $this->foGi22KpiUrl($filters, [
                'eficacia' => ServicioClienteCaso::EFICACIA_ABIERTO,
            ]),
            'en_proceso' => $this->foGi22KpiUrl($filters, [
                'eficacia' => ServicioClienteCaso::EFICACIA_EN_PROCESO,
            ]),
            'cerrados' => $this->foGi22KpiUrl($filters, [
                'eficacia' => ServicioClienteCaso::EFICACIA_CERRADO,
            ]),
            'vencidos' => $this->foGi22KpiUrl($filters, [
                'eficacia' => $eficacia !== '' ? $eficacia : 'todos',
                'vencidos' => '1',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $override
     */
    private function foGi22KpiUrl(array $filters, array $override): string
    {
        $shared = array_filter([
            'tipo_cliente' => (string) ($filters['tipo_cliente'] ?? ''),
            'tipologia_id' => (string) ($filters['tipologia_id'] ?? ''),
            'fuente_id' => (string) ($filters['fuente_id'] ?? ''),
            'responsable_id' => (string) ($filters['responsable_id'] ?? ''),
            'recepcion_desde' => (string) ($filters['recepcion_desde'] ?? ''),
            'recepcion_hasta' => (string) ($filters['recepcion_hasta'] ?? ''),
            'verificacion_desde' => (string) ($filters['verificacion_desde'] ?? ''),
            'verificacion_hasta' => (string) ($filters['verificacion_hasta'] ?? ''),
            'plazo_desde' => (string) ($filters['plazo_desde'] ?? ''),
            'plazo_hasta' => (string) ($filters['plazo_hasta'] ?? ''),
        ], static fn (string $value): bool => $value !== '');

        return route('calidad.servicio-cliente.fo-gi-22.index', array_merge($shared, $override));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    private function activeQuery(array $filters): array
    {
        return array_filter(
            $filters,
            static fn ($value): bool => $value !== null && $value !== '' && $value !== 'mes',
        );
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(Request $request): array
    {
        return array_values(array_filter(
            (array) $request->file('attachments', []),
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'tipos' => $this->pairOptions(config('servicio_cliente.tipos_cliente', [])),
            'eficacia' => $this->pairOptions(config('servicio_cliente.eficacia', [])),
            'recurrencia' => $this->pairOptions(config('servicio_cliente.recurrencia', [])),
            'tipologias' => $this->modelOptions(ServicioClienteTipologia::class),
            'fuentes' => $this->modelOptions(ServicioClienteFuente::class),
            'responsables' => $this->modelOptions(ServicioClienteResponsable::class),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?ServicioClienteCaso $caso = null): array
    {
        return [
            'tipos' => $this->pairOptions(config('servicio_cliente.tipos_cliente', [])),
            'eficacia' => $this->pairOptions(config('servicio_cliente.eficacia', [])),
            'tipologias' => $this->modelOptions(ServicioClienteTipologia::class, $caso?->tipologia_id),
            'fuentes' => $this->modelOptions(ServicioClienteFuente::class, $caso?->fuente_id),
            'categorias' => $this->modelOptions(ServicioClienteCategoria::class, $caso?->categoria_id),
            'responsables' => $this->modelOptions(ServicioClienteResponsable::class, $caso?->responsable_id),
            'ciudades' => RequisitionCity::query()
                ->where('is_active', true)
                ->orWhere('id', $caso?->city_id ?? 0)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (RequisitionCity $city): array => ['value' => (string) $city->id, 'label' => (string) $city->name])
                ->all(),
            'clientes' => CommercialClient::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (CommercialClient $client): array => ['value' => (string) $client->id, 'label' => (string) $client->name])
                ->all(),
        ];
    }

    /**
     * @param  array<string, string>  $pairs
     * @return list<array{value: string, label: string}>
     */
    private function pairOptions(array $pairs): array
    {
        $options = [];

        foreach ($pairs as $value => $label) {
            $options[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return $options;
    }

    /**
     * @param  class-string  $class
     * @return list<array{value: string, label: string}>
     */
    private function modelOptions(string $class, ?int $includeId = null): array
    {
        return $class::query()
            ->where('is_active', true)
            ->when($includeId, fn ($query) => $query->orWhere('id', $includeId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($item): array => ['value' => (string) $item->id, 'label' => (string) $item->name])
            ->all();
    }
}
