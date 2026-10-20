<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\SeleccionExamensExport;
use App\Exports\SeleccionIngresosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\StoreSeleccionCatalogItemRequest;
use App\Http\Requests\GestionHumana\StoreSeleccionExamenRequest;
use App\Http\Requests\GestionHumana\StoreSeleccionIngresoRequest;
use App\Http\Requests\GestionHumana\UpdateSeleccionCatalogItemRequest;
use App\Http\Requests\GestionHumana\UpdateSeleccionExamenRequest;
use App\Http\Requests\GestionHumana\UpdateSeleccionIngresoRequest;
use App\Models\CommercialClient;
use App\Models\PayrollCatalogItem;
use App\Models\RequisitionUniform;
use App\Models\SeleccionExamenOcupacional;
use App\Models\SeleccionIngreso;
use App\Services\Access\SeleccionAccessService;
use App\Services\GestionHumana\ReportesNovedadesFichaLookupService;
use App\Services\GestionHumana\SeleccionAuditLogService;
use App\Services\GestionHumana\SeleccionCatalogService;
use App\Services\GestionHumana\SeleccionDashboardService;
use App\Services\GestionHumana\SeleccionExamenDatatableService;
use App\Services\GestionHumana\SeleccionExamenHistorialService;
use App\Services\GestionHumana\SeleccionExamenService;
use App\Services\GestionHumana\SeleccionIngresoDatatableService;
use App\Services\GestionHumana\SeleccionIngresoService;
use App\Services\Requisitions\RequisitionSelectionOfficerAccessService;
use App\Traits\HasSeleccionTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeleccionController extends Controller
{
    use HasSeleccionTabs;

    public function __construct(
        private readonly SeleccionAccessService $seleccionAccess,
        private readonly SeleccionCatalogService $catalogService,
        private readonly SeleccionAuditLogService $auditLogService,
        private readonly SeleccionDashboardService $dashboardService,
        private readonly SeleccionIngresoService $ingresoService,
        private readonly SeleccionIngresoDatatableService $ingresoDatatableService,
        private readonly SeleccionExamenService $examenService,
        private readonly SeleccionExamenDatatableService $examenDatatableService,
        private readonly RequisitionSelectionOfficerAccessService $selectionOfficerAccess,
        private readonly SeleccionIngresosExport $ingresosExport,
        private readonly SeleccionExamensExport $examensExport,
        private readonly SeleccionExamenHistorialService $examenHistorialService,
        private readonly ReportesNovedadesFichaLookupService $fichaLookupService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        return redirect()->route('gestion-humana.seleccion.dashboard', $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $payload = $this->dashboardService->metrics($filters);

        return view('areas.gestion_humana.seleccion.dashboard', [
            'subTabs' => $this->getSeleccionSubTabs('dashboard'),
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.seleccion.dashboard.metrics'),
            'clientOptions' => array_merge(
                [['value' => '', 'label' => 'Todos']],
                $this->clientSelectOptions(),
            ),
            'responsableOptions' => array_merge(
                [['value' => '', 'label' => 'Todos']],
                $this->responsableSelectOptions(),
            ),
        ]);
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        return response()->json(
            $this->dashboardService->metrics($this->dashboardFiltersFromRequest($request))
        );
    }

    public function ingresos(Request $request): View
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        $filters = $this->ingresoFiltersFromRequest($request);
        $canEdit = $this->seleccionAccess->canEdit(auth()->user());

        return view('areas.gestion_humana.seleccion.ingresos', [
            'subTabs' => $this->getSeleccionSubTabs('ingresos'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'datatableUrl' => route('gestion-humana.seleccion.ingresos.datatable', array_filter($filters)),
            'exportUrl' => route('gestion-humana.seleccion.ingresos.export', array_filter($filters)),
            'lookupUrl' => route('gestion-humana.seleccion.ingresos.lookup-cedula'),
            'lookupPerfilUrl' => route('gestion-humana.seleccion.ingresos.lookup-perfil'),
            'storeUrl' => route('gestion-humana.seleccion.ingresos.store'),
            'cityOptions' => $this->catalogSelectOptions('city'),
            'positionOptions' => $this->catalogSelectOptions('position'),
            'bloodTypeOptions' => $this->catalogSelectOptions('blood_type'),
            'estadoOptions' => $this->catalogSelectOptions('ingreso_estado'),
            'jefeOperativoOptions' => $this->catalogSelectOptions('jefe_operativo'),
            'clientOptions' => $this->clientSelectOptions(),
            'uniformOptions' => $this->uniformSelectOptions(),
            'responsableOptions' => $this->responsableSelectOptions(),
            'showCreateModal' => $canEdit && $request->session()->get('errors') !== null
                && ! $request->session()->hasOldInput('_method'),
        ]);
    }

    public function ingresosDatatable(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        return $this->ingresoDatatableService->respond(
            $request,
            $this->ingresoFiltersFromRequest($request),
            $this->seleccionAccess->canEdit(auth()->user()),
        );
    }

    public function ingresosLookupCedula(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $cedula = trim((string) $request->query('cedula', ''));
        $excludeId = $request->query('exclude_id');
        $excludeId = $excludeId !== null && $excludeId !== '' ? (int) $excludeId : null;

        if ($cedula === '') {
            return response()->json([
                'has_duplicates' => false,
                'matches' => [],
            ]);
        }

        $matches = $this->ingresoService->findDuplicatesByDocument($cedula, $excludeId);

        return response()->json([
            'has_duplicates' => $matches->isNotEmpty(),
            'matches' => $this->ingresoService->duplicateSummaries($matches),
        ]);
    }

    /**
     * Busca la cédula de reemplazo en Ficha empleados y devuelve el nombre para «Reemplaza a».
     */
    public function ingresosLookupPerfil(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $result = $this->fichaLookupService->lookupByDocument((string) $request->query('cedula', ''));

        if (! ($result['found'] ?? false)) {
            return response()->json([
                'found' => false,
                'message' => 'No se encontró un perfil con esa cédula. Puede escribir el nombre manualmente.',
            ]);
        }

        return response()->json([
            'found' => true,
            'document_number' => (string) ($result['document_number'] ?? ''),
            'full_name' => (string) ($result['employee_name'] ?? ''),
        ]);
    }

    public function ingresosExport(Request $request): StreamedResponse
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        $rows = $this->ingresoService
            ->filteredQuery($this->ingresoFiltersFromRequest($request))
            ->get();

        return $this->ingresosExport->download($rows);
    }

    public function storeIngreso(StoreSeleccionIngresoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->assertIngresoDuplicateConfirmed(
            (string) $validated['document_number'],
            $request->boolean('confirm_duplicate'),
        );

        $payload = $this->ingresoService->payloadWithSnapshots($validated);

        $ingreso = SeleccionIngreso::query()->create([
            ...$payload,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'seleccion_ingreso',
            action: 'create',
            metadata: [
                'seleccion_ingreso_id' => $ingreso->id,
                'document_number' => $ingreso->document_number,
            ],
            model: $ingreso,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.ingresos')
            ->with('status', 'Registro de ingreso creado correctamente.');
    }

    public function updateIngreso(UpdateSeleccionIngresoRequest $request, SeleccionIngreso $seleccionIngreso): RedirectResponse
    {
        $validated = $request->validated();
        $this->assertIngresoDuplicateConfirmed(
            (string) $validated['document_number'],
            $request->boolean('confirm_duplicate'),
            $seleccionIngreso->id,
        );

        $before = $seleccionIngreso->only([
            'document_number',
            'full_name',
            'email',
            'phone',
            'city_code',
            'city_name',
            'position_code',
            'position_name',
            'commercial_client_id',
            'shirt_size',
            'pants_size',
            'shoes_size',
            'requisition_uniform_id',
            'fecha_ingreso',
            'fecha_asistencia',
            'estado_code',
            'estado_name',
            'blood_type_code',
            'blood_type_name',
            'reemplaza_cedula',
            'reemplaza_a',
            'responsable_user_id',
            'jefe_ope',
            'jefe_ope_code',
            'observaciones',
        ]);

        $payload = $this->ingresoService->payloadWithSnapshots($validated);

        $seleccionIngreso->update([
            ...$payload,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'seleccion_ingreso',
            action: 'update',
            model: $seleccionIngreso,
            before: $before,
            after: $seleccionIngreso->only(array_keys($before)),
            metadata: [
                'seleccion_ingreso_id' => $seleccionIngreso->id,
                'document_number' => $seleccionIngreso->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.ingresos', $request->query())
            ->with('status', 'Registro de ingreso actualizado correctamente.');
    }

    public function destroyIngreso(SeleccionIngreso $seleccionIngreso): RedirectResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $metadata = [
            'seleccion_ingreso_id' => $seleccionIngreso->id,
            'document_number' => $seleccionIngreso->document_number,
        ];

        $seleccionIngreso->delete();

        $this->auditLogService->logEvent(
            eventType: 'seleccion_ingreso',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.ingresos')
            ->with('status', 'Registro de ingreso eliminado.');
    }

    public function examenes(Request $request): View
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        $filters = $this->examenFiltersFromRequest($request);
        $canEdit = $this->seleccionAccess->canEdit(auth()->user());

        // Acción «Pasar a examen» desde Ingreso: abre Nuevo examen con los datos coincidentes.
        $prefillIngreso = null;
        $desdeIngreso = $request->query('desde_ingreso');
        if ($canEdit && $desdeIngreso !== null && $desdeIngreso !== '') {
            $prefillIngreso = SeleccionIngreso::query()->find((int) $desdeIngreso);
        }
        $hasErrors = $request->session()->get('errors') !== null;

        return view('areas.gestion_humana.seleccion.examenes', [
            'subTabs' => $this->getSeleccionSubTabs('examenes'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'datatableUrl' => route('gestion-humana.seleccion.examenes.datatable', array_filter($filters)),
            'exportUrl' => route('gestion-humana.seleccion.examenes.export', array_filter($filters)),
            'lookupUrl' => route('gestion-humana.seleccion.examenes.lookup-cedula'),
            'storeUrl' => route('gestion-humana.seleccion.examenes.store'),
            'cityOptions' => $this->catalogSelectOptions('city'),
            'positionOptions' => $this->catalogSelectOptions('position'),
            'epsOptions' => $this->catalogSelectOptions('eps'),
            'afpOptions' => $this->catalogSelectOptions('afp'),
            'maritalStatusOptions' => $this->catalogSelectOptions('marital_status'),
            'solicitudStatusOptions' => $this->catalogSelectOptions('seleccion_solicitud_status'),
            'estadoLabels' => SeleccionExamenOcupacional::ESTADO_LABELS,
            'estadoOptions' => collect(SeleccionExamenOcupacional::ESTADO_LABELS)
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'clientOptions' => $this->clientSelectOptions(),
            'responsableOptions' => $this->responsableSelectOptions(),
            'historialUrl' => $canEdit ? route('gestion-humana.seleccion.examenes.historial') : null,
            'historialUserOptions' => $canEdit ? $this->examenHistorialService->userOptions() : [],
            'lookupIngresoUrl' => $canEdit ? route('gestion-humana.seleccion.examenes.lookup-ingreso') : null,
            'createPrefill' => $prefillIngreso !== null && ! $hasErrors
                ? $this->examenService->prefillFromIngreso($prefillIngreso)
                : [],
            'prefillIngresoId' => $prefillIngreso?->id,
            'showCreateModal' => $canEdit && (
                ($hasErrors && ! $request->session()->hasOldInput('_method'))
                || $prefillIngreso !== null
            ),
        ]);
    }

    /**
     * Busca el ingreso más reciente por cédula y devuelve los campos coincidentes para Nuevo examen.
     */
    public function examenesLookupIngreso(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $ingreso = $this->examenService->latestIngresoByDocument((string) $request->query('cedula', ''));

        if ($ingreso === null) {
            return response()->json([
                'found' => false,
                'message' => 'No hay ingreso registrado con esa cédula. Complete los datos manualmente.',
            ]);
        }

        return response()->json([
            'found' => true,
            'ingreso_id' => $ingreso->id,
            'data' => $this->examenService->prefillFromIngreso($ingreso),
        ]);
    }

    /**
     * Historial de auditoría de Exámenes (global con filtros o `?id=` por registro). Solo `seleccion.edit`.
     */
    public function examenesHistorial(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $rowId = $request->query('id');
        $rowId = $rowId !== null && $rowId !== '' ? (int) $rowId : null;

        return response()->json([
            'data' => $this->examenHistorialService->entries(
                $rowId,
                $rowId === null ? $this->examenHistorialService->filtersFromRequest($request) : [],
            ),
        ]);
    }

    public function examenesDatatable(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        return $this->examenDatatableService->respond(
            $request,
            $this->examenFiltersFromRequest($request),
            $this->seleccionAccess->canEdit(auth()->user()),
        );
    }

    public function examenesLookupCedula(Request $request): JsonResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $cedula = trim((string) $request->query('cedula', ''));
        $excludeId = $request->query('exclude_id');
        $excludeId = $excludeId !== null && $excludeId !== '' ? (int) $excludeId : null;

        if ($cedula === '') {
            return response()->json([
                'has_duplicates' => false,
                'matches' => [],
            ]);
        }

        $matches = $this->examenService->findDuplicatesByDocument($cedula, $excludeId);

        return response()->json([
            'has_duplicates' => $matches->isNotEmpty(),
            'matches' => $this->examenService->duplicateSummaries($matches),
        ]);
    }

    public function examenesExport(Request $request): StreamedResponse
    {
        abort_unless($this->seleccionAccess->canView(auth()->user()), 403);

        $rows = $this->examenService
            ->filteredQuery($this->examenFiltersFromRequest($request))
            ->get();

        return $this->examensExport->download($rows);
    }

    public function storeExamen(StoreSeleccionExamenRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->assertExamenDuplicateConfirmed(
            (string) $validated['document_number'],
            $request->boolean('confirm_duplicate'),
        );

        $payload = $this->examenService->payloadWithSnapshots($validated);

        $examen = SeleccionExamenOcupacional::query()->create([
            ...$payload,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Alta con valores iniciales para el historial (before vacío).
        $this->auditLogService->logModelChange(
            eventType: 'seleccion_examen',
            action: 'create',
            model: $examen,
            before: null,
            after: $this->examenService->auditSnapshot($examen->fresh()),
            metadata: [
                'seleccion_examen_id' => $examen->id,
                'document_number' => $examen->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.examenes')
            ->with('status', 'Registro de examen ocupacional creado correctamente.');
    }

    public function updateExamen(UpdateSeleccionExamenRequest $request, SeleccionExamenOcupacional $seleccionExamenOcupacional): RedirectResponse
    {
        $validated = $request->validated();
        $this->assertExamenDuplicateConfirmed(
            (string) $validated['document_number'],
            $request->boolean('confirm_duplicate'),
            $seleccionExamenOcupacional->id,
        );

        $before = $this->examenService->auditSnapshot($seleccionExamenOcupacional);

        $payload = $this->examenService->payloadWithSnapshots($validated);

        $seleccionExamenOcupacional->update([
            ...$payload,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'seleccion_examen',
            action: 'update',
            model: $seleccionExamenOcupacional,
            before: $before,
            after: $this->examenService->auditSnapshot($seleccionExamenOcupacional->fresh()),
            metadata: [
                'seleccion_examen_id' => $seleccionExamenOcupacional->id,
                'document_number' => $seleccionExamenOcupacional->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.examenes', $request->query())
            ->with('status', 'Registro de examen ocupacional actualizado correctamente.');
    }

    public function destroyExamen(SeleccionExamenOcupacional $seleccionExamenOcupacional): RedirectResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        $metadata = [
            'seleccion_examen_id' => $seleccionExamenOcupacional->id,
            'document_number' => $seleccionExamenOcupacional->document_number,
        ];

        $seleccionExamenOcupacional->delete();

        $this->auditLogService->logEvent(
            eventType: 'seleccion_examen',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.examenes')
            ->with('status', 'Registro de examen ocupacional eliminado.');
    }

    public function catalogos(): View
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);

        return view('areas.gestion_humana.seleccion.catalogos', [
            'subTabs' => $this->getSeleccionSubTabs('catalogos'),
            'catalogs' => $this->catalogService->catalogsForAdmin(),
        ]);
    }

    public function storeCatalog(StoreSeleccionCatalogItemRequest $request, string $type): RedirectResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);
        abort_unless($this->catalogService->isManagedType($type), 404);

        $name = $request->string('name')->toString();
        $codeless = $this->catalogService->isCodeless($type);

        $item = PayrollCatalogItem::query()->create([
            'catalog_type' => $type,
            'code' => $codeless
                ? $this->catalogService->makeUniqueCode($type, $name)
                : $request->string('code')->toString(),
            'name' => $name,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $codeless
                ? $this->catalogService->nextSortOrder($type)
                : (int) ($request->input('sort_order') ?? 0),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'seleccion_catalog',
            action: 'create',
            metadata: [
                'catalog_type' => $type,
                'code' => $item->code,
                'payroll_catalog_item_id' => $item->id,
            ],
            model: $item,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.catalogos', ['catalog' => $type])
            ->with('status', 'Catálogo actualizado correctamente.');
    }

    public function updateCatalog(UpdateSeleccionCatalogItemRequest $request, string $type, PayrollCatalogItem $item): RedirectResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);
        abort_unless($this->catalogService->isManagedType($type), 404);
        abort_unless($item->catalog_type === $type, 404);

        $before = $item->only(['code', 'name', 'is_active', 'sort_order']);

        $codeless = $this->catalogService->isCodeless($type);

        $item->update([
            'code' => $codeless ? $item->code : $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $codeless
                ? ($item->sort_order ?? 0)
                : (int) ($request->input('sort_order') ?? $item->sort_order ?? 0),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'seleccion_catalog',
            action: 'update',
            model: $item,
            before: $before,
            after: $item->only(array_keys($before)),
            metadata: [
                'catalog_type' => $type,
                'code' => $item->code,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.catalogos', ['catalog' => $type])
            ->with('status', 'Registro de catálogo actualizado.');
    }

    public function destroyCatalog(string $type, PayrollCatalogItem $item): RedirectResponse
    {
        abort_unless($this->seleccionAccess->canEdit(auth()->user()), 403);
        abort_unless($this->catalogService->isManagedType($type), 404);
        abort_unless($item->catalog_type === $type, 404);

        if ($this->catalogService->hasBusinessReferences($item)) {
            return redirect()
                ->route('gestion-humana.seleccion.catalogos', ['catalog' => $type])
                ->with('error', 'No se puede eliminar: hay registros que usan este valor. Desactívelo en su lugar.');
        }

        $metadata = [
            'catalog_type' => $type,
            'code' => $item->code,
            'payroll_catalog_item_id' => $item->id,
        ];

        $item->delete();

        $this->auditLogService->logEvent(
            eventType: 'seleccion_catalog',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.seleccion.catalogos', ['catalog' => $type])
            ->with('status', 'Registro eliminado del catálogo.');
    }

    /**
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     commercial_client_id: string,
     *     responsable_user_id: string
     * }
     */
    private function dashboardFiltersFromRequest(Request $request): array
    {
        return $this->dashboardService->normalizeFilters([
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'commercial_client_id' => (string) $request->query('commercial_client_id', ''),
            'responsable_user_id' => (string) $request->query('responsable_user_id', ''),
        ]);
    }

    /**
     * @return array{
     *     q: string,
     *     date_from: string,
     *     date_to: string,
     *     asistencia_from: string,
     *     asistencia_to: string,
     *     estado_code: string,
     *     commercial_client_id: string,
     *     responsable_user_id: string,
     *     city_code: string,
     *     position_code: string
     * }
     */
    private function ingresoFiltersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'asistencia_from' => trim((string) $request->query('asistencia_from', '')),
            'asistencia_to' => trim((string) $request->query('asistencia_to', '')),
            'estado_code' => trim((string) $request->query('estado_code', '')),
            'commercial_client_id' => trim((string) $request->query('commercial_client_id', '')),
            'responsable_user_id' => trim((string) $request->query('responsable_user_id', '')),
            'city_code' => trim((string) $request->query('city_code', '')),
            'position_code' => trim((string) $request->query('position_code', '')),
        ];
    }

    /**
     * @return array{
     *     q: string,
     *     date_from: string,
     *     date_to: string,
     *     commercial_client_id: string,
     *     responsable_user_id: string,
     *     city_code: string,
     *     position_code: string,
     *     solicitud_status_code: string
     * }
     */
    private function examenFiltersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'commercial_client_id' => trim((string) $request->query('commercial_client_id', '')),
            'responsable_user_id' => trim((string) $request->query('responsable_user_id', '')),
            'city_code' => trim((string) $request->query('city_code', '')),
            'position_code' => trim((string) $request->query('position_code', '')),
            'solicitud_status_code' => trim((string) $request->query('solicitud_status_code', '')),
            'estado' => array_key_exists((string) $request->query('estado', ''), SeleccionExamenOcupacional::ESTADO_LABELS)
                ? (string) $request->query('estado')
                : '',
        ];
    }

    private function assertIngresoDuplicateConfirmed(string $documentNumber, bool $confirmed, ?int $excludeId = null): void
    {
        $matches = $this->ingresoService->findDuplicatesByDocument($documentNumber, $excludeId);

        if ($matches->isEmpty() || $confirmed) {
            return;
        }

        $summaries = $this->ingresoService->duplicateSummaries($matches);
        $detail = collect($summaries)
            ->map(fn (array $row): string => sprintf('#%d (%s)', $row['id'], $row['fecha_ingreso'] ?? 's/f'))
            ->implode(', ');

        throw ValidationException::withMessages([
            'confirm_duplicate' => 'Ya existen ingresos con esta cédula: '.$detail.'. Confirme para guardar de todos modos.',
            'document_number' => 'Cédula duplicada en Ingreso. Se requiere confirmación.',
        ])->errorBag('default', [
            'duplicates' => $summaries,
        ]);
    }

    private function assertExamenDuplicateConfirmed(string $documentNumber, bool $confirmed, ?int $excludeId = null): void
    {
        $matches = $this->examenService->findDuplicatesByDocument($documentNumber, $excludeId);

        if ($matches->isEmpty() || $confirmed) {
            return;
        }

        $summaries = $this->examenService->duplicateSummaries($matches);
        $detail = collect($summaries)
            ->map(fn (array $row): string => sprintf('#%d (%s)', $row['id'], $row['fecha_arl'] ?? 's/f'))
            ->implode(', ');

        throw ValidationException::withMessages([
            'confirm_duplicate' => 'Ya existen exámenes con esta cédula: '.$detail.'. Confirme para guardar de todos modos.',
            'document_number' => 'Cédula duplicada en Examen ocupacional. Se requiere confirmación.',
        ])->errorBag('default', [
            'duplicates' => $summaries,
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function catalogSelectOptions(string $type): array
    {
        return PayrollCatalogItem::query()
            ->ofType($type)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (PayrollCatalogItem $item): array => [
                'value' => $item->code,
                'label' => $item->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function clientSelectOptions(): array
    {
        return CommercialClient::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CommercialClient $client): array => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function uniformSelectOptions(): array
    {
        return RequisitionUniform::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (RequisitionUniform $uniform): array => [
                'value' => (string) $uniform->id,
                'label' => $uniform->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function responsableSelectOptions(): array
    {
        return $this->selectionOfficerAccess
            ->selectableSelectionOfficers()
            ->map(fn ($user): array => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ])
            ->values()
            ->all();
    }
}
