<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Exports\BaseExport;
use App\Exports\PurchaseProviderImportTemplateExport;
use App\Http\Controllers\Concerns\HandlesImportFailureReports;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ImportPurchaseProvidersRequest;
use App\Http\Requests\PurchaseRequests\StorePurchaseProviderRequest;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseProviderRequest;
use App\Models\PurchaseProvider;
use App\Models\RequisitionCity;
use App\Services\Access\PurchaseAccessService;
use App\Services\Audit\SystemAuditService;
use App\Services\PurchaseRequests\Providers\PurchaseProviderDatatableService;
use App\Services\PurchaseRequests\Providers\PurchaseProviderImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseProviderController extends Controller
{
    use HandlesImportFailureReports;

    private const IMPORT_REPORT_MODULE = 'purchase_providers';

    /**
     * @var list<string>
     */
    private const AUDIT_FIELDS = [
        'identificacion',
        'nombre',
        'telefono',
        'correo',
        'estado',
        'dias_pago',
        'contacto',
        'direccion',
        'ciudad',
        'forma_pago',
    ];

    public function __construct(
        private readonly PurchaseProviderDatatableService $datatableService,
        private readonly PurchaseAccessService $accessService,
        private readonly SystemAuditService $auditLogService,
    ) {}

    public function index(string $module, Request $request): View
    {
        $filters = $this->datatableService->filtersFromRequest($request);
        $returnQuery = $this->datatableService->filtersToQuery($filters);

        // El buscador es el de DataTables (`search.value`): el término no viaja
        // en la URL del listado para no duplicar el filtro.
        $datatableQuery = $returnQuery;
        unset($datatableQuery['q']);

        return view('modules.purchase-requests.providers.index', [
            'module' => $module,
            'filters' => $filters,
            'returnQuery' => $returnQuery,
            'canEdit' => $this->accessService->canEditProviders($request->user()),
            'estados' => [
                PurchaseProvider::ESTADO_ACTIVO => 'Activos',
                PurchaseProvider::ESTADO_INACTIVO => 'Inactivos',
                PurchaseProviderDatatableService::ESTADO_TODOS => 'Todos',
            ],
            'datatableUrl' => route('purchase-requests.providers.datatable', array_merge(
                ['module' => $module],
                $datatableQuery,
            )),
            'exportUrl' => route('purchase-requests.providers.export', array_merge(
                ['module' => $module],
                $returnQuery,
            )),
            'createUrl' => route('purchase-requests.providers.create', array_merge(
                ['module' => $module],
                $returnQuery,
            )),
            'importTemplateUrl' => route('purchase-requests.providers.template', ['module' => $module]),
            'importUrl' => route('purchase-requests.providers.import', array_merge(
                ['module' => $module],
                $returnQuery,
            )),
            'showImportModal' => (bool) session('import_modal_open', false),
            'indexUrl' => route('purchase-requests.providers.index', ['module' => $module]),
        ]);
    }

    public function datatable(string $module, Request $request): JsonResponse
    {
        return $this->datatableService->respond(
            $request,
            $this->datatableService->filtersFromRequest($request),
            $this->accessService->canEditProviders($request->user()),
        );
    }

    public function create(string $module, Request $request): View
    {
        return view(
            'modules.purchase-requests.providers.create',
            $this->formData($module, new PurchaseProvider, $request, isEdit: false),
        );
    }

    public function store(StorePurchaseProviderRequest $request, string $module): RedirectResponse
    {
        // El catálogo solo gestiona proveedores.
        $provider = PurchaseProvider::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        $this->auditLogService->logModelChange(
            module: 'purchase_requests',
            eventType: 'provider_created',
            action: 'create',
            model: $provider,
            before: null,
            after: $provider->only(self::AUDIT_FIELDS),
            area: 'compras',
        );

        return $this->redirectToIndex($module, $request)
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(string $module, PurchaseProvider $purchaseProvider, Request $request): View
    {
        return view(
            'modules.purchase-requests.providers.edit',
            $this->formData($module, $purchaseProvider, $request, isEdit: true),
        );
    }

    public function update(
        UpdatePurchaseProviderRequest $request,
        string $module,
        PurchaseProvider $purchaseProvider,
    ): RedirectResponse {
        $before = $purchaseProvider->only(self::AUDIT_FIELDS);

        $purchaseProvider->update($request->validated());

        $this->auditLogService->logModelChange(
            module: 'purchase_requests',
            eventType: 'provider_updated',
            action: 'update',
            model: $purchaseProvider->fresh(),
            before: $before,
            after: $purchaseProvider->fresh()?->only(self::AUDIT_FIELDS),
            area: 'compras',
        );

        return $this->redirectToIndex($module, $request)
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    public function export(string $module, Request $request): StreamedResponse
    {
        $filters = $this->datatableService->filtersFromRequest($request);

        $providers = $this->datatableService->filteredQuery($filters)
            ->orderBy('nombre')
            ->get();

        $columns = [
            ['key' => 'identificacion', 'label' => 'Identificación'],
            ['key' => 'nombre', 'label' => 'Nombre / razón social'],
            ['key' => 'telefono', 'label' => 'Teléfono'],
            ['key' => 'correo', 'label' => 'Correo'],
            ['key' => 'contacto', 'label' => 'Contacto'],
            ['key' => 'direccion', 'label' => 'Dirección'],
            ['key' => 'ciudad', 'label' => 'Ciudad'],
            ['key' => 'forma_pago', 'label' => 'Forma de pago'],
            ['key' => 'dias_pago', 'label' => 'Días de pago'],
            [
                'key' => fn (PurchaseProvider $provider): string => PurchaseProvider::estadoLabels()[$provider->estado] ?? $provider->estado,
                'label' => 'Estado',
            ],
        ];

        $this->auditLogService->logEvent(
            module: 'purchase_requests',
            eventType: 'provider_export',
            action: 'providers_catalog_excel',
            metadata: ['row_count' => $providers->count()],
            area: 'compras',
        );

        return (new BaseExport(
            $providers,
            $columns,
            'proveedores_'.now()->format('Y-m-d').'.xlsx',
            'Catálogo de proveedores',
        ))->download();
    }

    public function template(string $module, PurchaseProviderImportTemplateExport $export): StreamedResponse
    {
        return $export->download('plantilla_proveedores_VINE01.xlsx');
    }

    /**
     * Importa la plantilla VINE01. Solo carga filas con PROVEEDOR = SI y
     * actualiza por `identificacion`.
     */
    public function import(
        ImportPurchaseProvidersRequest $request,
        string $module,
        PurchaseProviderImportService $importService,
    ): RedirectResponse {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors([
                'import_file' => 'No se pudo leer el archivo subido.',
            ]);
        }

        try {
            $report = $importService->import($path, false, null, $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'import_file' => $e->getMessage(),
            ]);
        }

        $this->auditLogService->logEvent(
            module: 'purchase_requests',
            eventType: 'providers_imported',
            action: 'import',
            metadata: [
                'created' => $report['created'],
                'updated' => $report['updated'],
                'skipped' => $report['skipped'],
                'errors_count' => $report['errors_count'],
            ],
            area: 'compras',
        );

        $status = sprintf(
            'Importación VINE01: %d creado(s), %d actualizado(s), %d omitido(s) (no proveedor).',
            $report['created'],
            $report['updated'],
            $report['skipped'],
        );

        $importResult = $this->buildImportResultPayload(
            $request->user(),
            [
                'imported' => $report['created'],
                'created' => $report['created'],
                'updated' => $report['updated'],
                'skipped' => $report['skipped'],
                'empty_rows' => $report['skipped_empty'],
                'skipped_empty' => $report['skipped_empty'],
                'processed' => $report['processed'],
                'failures' => $report['failures'],
                'errors_count' => $report['errors_count'],
                'dry_run' => false,
            ],
            self::IMPORT_REPORT_MODULE,
            'Proveedores (VINE01)',
            [
                'Proveedores nuevos' => $report['created'],
                'Proveedores actualizados' => $report['updated'],
                'Filas omitidas (no proveedor)' => $report['skipped'],
                'Filas vacías' => $report['skipped_empty'],
                'Filas con error' => $report['errors_count'],
            ],
            array_keys(config('purchase-requests.providers_import_columns', [])),
            'reporte_importacion_proveedores',
        );

        $importResult['skipped_label'] = 'Omitidos';

        // Tabla de errores del modal: primeras 50 filas; el reporte descargable
        // conserva todas las filas fallidas.
        $displayLimit = 50;
        $displayFailures = array_slice($report['failures'], 0, $displayLimit);

        $importResult['errors'] = array_map(static fn (array $failure): array => [
            'row' => $failure['row'] ?? '—',
            'identifier' => (string) ($failure['identifier'] ?? ''),
            'reason' => (string) ($failure['reason'] ?? ''),
        ], $displayFailures);
        $importResult['failures_count'] = $report['errors_count'];
        $importResult['errors_total'] = $report['errors_count'];
        $importResult['errors_truncated'] = $report['errors_count'] > count($displayFailures);

        return $this->redirectToIndex($module, $request)
            ->with('status', $status)
            ->with('import_done', true)
            ->with('import_result', $importResult);
    }

    /**
     * Descarga el reporte Excel de filas fallidas de la última carga masiva.
     */
    public function downloadImportReport(Request $request, string $module, string $token): StreamedResponse
    {
        abort_unless($this->accessService->canEditProviders($request->user()), 403);

        return $this->downloadImportFailureReport($request->user(), $token, self::IMPORT_REPORT_MODULE);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(
        string $module,
        PurchaseProvider $provider,
        Request $request,
        bool $isEdit,
    ): array {
        $returnQuery = $this->datatableService->filtersToQuery(
            $this->datatableService->filtersFromQuery($request),
        );

        $baseRouteParameters = array_merge(['module' => $module], $returnQuery);

        return [
            'module' => $module,
            'provider' => $provider,
            'estados' => PurchaseProvider::estadoLabels(),
            'ciudades' => $this->cityOptions($provider),
            'returnQuery' => $returnQuery,
            'formAction' => $isEdit
                ? route('purchase-requests.providers.update', array_merge($baseRouteParameters, [
                    'purchase_provider' => $provider,
                ]))
                : route('purchase-requests.providers.store', $baseRouteParameters),
            'cancelUrl' => route('purchase-requests.providers.index', $baseRouteParameters),
            'pageTitle' => $isEdit ? 'Editar proveedor' : 'Nuevo proveedor',
        ];
    }

    private function redirectToIndex(string $module, Request $request): RedirectResponse
    {
        $returnQuery = $this->datatableService->filtersToQuery(
            $this->datatableService->filtersFromQuery($request),
        );

        return redirect()->route('purchase-requests.providers.index', array_merge(
            ['module' => $module],
            $returnQuery,
        ));
    }

    /**
     * Ciudades activas del catálogo de Requisiciones (requisition_cities).
     * Si el proveedor ya tiene una ciudad que no está en el catálogo, se conserva
     * como opción para no perder el valor en edición.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function cityOptions(PurchaseProvider $provider): array
    {
        $names = RequisitionCity::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name): bool => is_string($name) && trim($name) !== '')
            ->values();

        $options = $names
            ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
            ->all();

        $current = trim((string) ($provider->ciudad ?? ''));

        if ($current !== '' && ! $names->contains($current)) {
            $options[] = ['value' => $current, 'label' => $current.' (actual)'];
        }

        return $options;
    }
}
