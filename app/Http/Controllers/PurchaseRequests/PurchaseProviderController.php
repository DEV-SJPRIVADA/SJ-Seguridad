<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\StorePurchaseProviderRequest;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseProviderRequest;
use App\Models\PurchaseProvider;
use App\Services\Access\PurchaseAccessService;
use App\Services\Audit\SystemAuditService;
use App\Services\PurchaseRequests\Providers\PurchaseProviderDatatableService;
use App\Traits\HasPurchaseTabs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseProviderController extends Controller
{
    use HasPurchaseTabs;

    /**
     * @var list<string>
     */
    private const AUDIT_FIELDS = [
        'identificacion',
        'nombre',
        'telefono',
        'correo',
        'es_cliente',
        'es_proveedor',
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

        return view('modules.purchase-requests.providers.index', [
            'module' => $module,
            'subTabs' => $this->getPurchaseSubTabs($module),
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
                $returnQuery,
            )),
            'exportUrl' => route('purchase-requests.providers.export', array_merge(
                ['module' => $module],
                $returnQuery,
            )),
            'createUrl' => route('purchase-requests.providers.create', array_merge(
                ['module' => $module],
                $returnQuery,
            )),
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
                'key' => fn (PurchaseProvider $provider): string => $provider->es_cliente ? 'Sí' : 'No',
                'label' => 'Cliente',
            ],
            [
                'key' => fn (PurchaseProvider $provider): string => $provider->es_proveedor ? 'Sí' : 'No',
                'label' => 'Proveedor',
            ],
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
            'subTabs' => $this->getPurchaseSubTabs($module),
            'provider' => $provider,
            'estados' => PurchaseProvider::estadoLabels(),
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
}
