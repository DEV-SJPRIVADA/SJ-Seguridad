<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\ClienteInternoExport;
use App\Exports\ClienteInternoImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ClienteInterno\GenerateCartasVacacionesRequest;
use App\Http\Requests\GestionHumana\ClienteInterno\ImportClienteInternoSolicitudesRequest;
use App\Http\Requests\GestionHumana\ClienteInterno\LookupCartasVacacionesRequest;
use App\Http\Requests\GestionHumana\ClienteInterno\StoreClienteInternoCatalogItemRequest;
use App\Http\Requests\GestionHumana\ClienteInterno\StoreClienteInternoSolicitudRequest;
use App\Http\Requests\GestionHumana\ClienteInterno\UpdateClienteInternoCatalogItemRequest;
use App\Http\Requests\GestionHumana\ClienteInterno\UpdateClienteInternoSolicitudRequest;
use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use App\Models\ClienteInternoTipoSolicitud;
use App\Models\PayrollCatalogItem;
use App\Services\Access\ClienteInternoAccessService;
use App\Services\GestionHumana\ClienteInternoAuditLogService;
use App\Services\GestionHumana\ClienteInternoBusinessDaysService;
use App\Services\GestionHumana\ClienteInternoCartasVacacionesGeneratorService;
use App\Services\GestionHumana\ClienteInternoCatalogService;
use App\Services\GestionHumana\ClienteInternoDashboardService;
use App\Services\GestionHumana\ClienteInternoDatatableService;
use App\Services\GestionHumana\ClienteInternoImportService;
use App\Support\ColombiaHolidays;
use App\Traits\HasClienteInternoTabs;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteInternoController extends Controller
{
    use HasClienteInternoTabs;

    public function __construct(
        private readonly ClienteInternoAccessService $clienteInternoAccess,
        private readonly ClienteInternoCatalogService $catalogService,
        private readonly ClienteInternoAuditLogService $auditLogService,
        private readonly ClienteInternoDashboardService $dashboardService,
        private readonly ClienteInternoDatatableService $datatableService,
        private readonly ClienteInternoBusinessDaysService $businessDaysService,
        private readonly ClienteInternoImportService $importService,
        private readonly ClienteInternoImportTemplateExport $importTemplateExport,
        private readonly ClienteInternoCartasVacacionesGeneratorService $cartasVacacionesGenerator,
        private readonly ColombiaHolidays $colombiaHolidays,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->clienteInternoAccess->canViewBoard($user), 403);

        $tabs = $this->clienteInternoAccess->visibleTabsFor($user);
        $firstTab = $tabs[0] ?? null;
        abort_unless($firstTab !== null, 403);

        $route = match ($firstTab) {
            'solicitudes' => 'gestion-humana.cliente-interno.solicitudes',
            'cartas_vacaciones' => 'gestion-humana.cliente-interno.cartas-vacaciones',
            'catalogos' => 'gestion-humana.cliente-interno.catalogos',
            default => 'gestion-humana.cliente-interno.dashboard',
        };

        return redirect()->route($route, $request->query());
    }

    public function cartasVacaciones(): View
    {
        $user = auth()->user();
        abort_unless($this->clienteInternoAccess->canViewCartasVacaciones($user), 403);

        $canEdit = $this->clienteInternoAccess->canEditCartasVacaciones($user);

        $year = (int) now()->year;

        return view('areas.gestion_humana.cliente_interno.cartas-vacaciones', [
            'subTabs' => $this->getClienteInternoSubTabs('cartas_vacaciones'),
            'canEditCartasVacaciones' => $canEdit,
            'signatoryOptions' => $canEdit ? $this->cartasVacacionesSignatoryOptions() : [],
            'maxRows' => (int) config('cliente_interno.cartas_vacaciones.max_rows', 500),
            // Festivos CO para sugerir fecha fin en la grilla (rango amplio; cruza años).
            'holidayDates' => $this->colombiaHolidays->isoDatesForYears($year - 2, $year + 4),
            'lookupUrl' => route('gestion-humana.cliente-interno.cartas-vacaciones.lookup'),
            'generateUrl' => route('gestion-humana.cliente-interno.cartas-vacaciones.generate'),
        ]);
    }

    public function cartasVacacionesLookup(LookupCartasVacacionesRequest $request): JsonResponse
    {
        $results = $this->cartasVacacionesGenerator->lookup($request->documentNumbers());

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    public function cartasVacacionesGenerate(GenerateCartasVacacionesRequest $request): BinaryFileResponse
    {
        $result = $this->cartasVacacionesGenerator->generate($request->rows());

        $this->auditLogService->logEvent(
            eventType: 'cartas_vacaciones_generate',
            action: 'generate',
            metadata: [
                'row_count' => $result['row_count'],
                'output_type' => $result['output_type'],
                'template_id' => $result['template_id'],
            ],
            userId: (int) auth()->id(),
        );

        return response()
            ->download($result['absolute_path'], $result['download_name'])
            ->deleteFileAfterSend(true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function cartasVacacionesSignatoryOptions(): array
    {
        return PayrollCatalogItem::query()
            ->ofType('firmas')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(static fn (PayrollCatalogItem $item): array => [
                'value' => (string) $item->id,
                'label' => $item->name.' — '.$item->code,
            ])
            ->values()
            ->all();
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->clienteInternoAccess->canViewDashboard(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $anio = $filters['anio'] !== '' ? (int) $filters['anio'] : null;
        $payload = $this->dashboardService->metrics($anio, $filters);
        $options = $this->dashboardService->filterSelectOptions($payload['anio']);

        return view('areas.gestion_humana.cliente_interno.dashboard', [
            'subTabs' => $this->getClienteInternoSubTabs('dashboard'),
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.cliente-interno.dashboard.metrics'),
            'solicitudesUrl' => route('gestion-humana.cliente-interno.solicitudes'),
            'canViewSolicitudes' => $this->clienteInternoAccess->canViewSolicitudes(auth()->user()),
            'yearOptions' => $options['anios'],
            'monthOptions' => $options['meses'],
        ]);
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        abort_unless($this->clienteInternoAccess->canViewDashboard(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $anio = $filters['anio'] !== '' ? (int) $filters['anio'] : null;

        return response()->json(
            $this->dashboardService->metrics($anio, $filters)
        );
    }

    public function solicitudes(Request $request): View
    {
        abort_unless($this->clienteInternoAccess->canViewSolicitudes(auth()->user()), 403);

        $filters = $this->solicitudFiltersFromRequest($request);
        $options = $this->datatableService->filterSelectOptions();
        $canEdit = $this->clienteInternoAccess->canEditSolicitudes(auth()->user());
        $activeQuery = array_filter(
            $filters,
            static fn ($value): bool => $value !== null && $value !== '',
        );

        $tipoOptions = ClienteInternoTipoSolicitud::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ClienteInternoTipoSolicitud $tipo): array => [
                'value' => (string) $tipo->id,
                'label' => (string) $tipo->name,
            ])
            ->values()
            ->all();

        $estadoFormOptions = ClienteInternoEstado::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ClienteInternoEstado $estado): array => [
                'value' => (string) $estado->id,
                'label' => (string) $estado->name,
            ])
            ->values()
            ->all();

        $errorBag = $request->session()->get('errors');
        $hasImportErrors = $errorBag !== null
            && (
                $errorBag->has('import_file')
                || $errorBag->has('confirm_replace')
                || $errorBag->has('anio')
                || $errorBag->has('mes')
            );

        $showImportModal = $canEdit && $hasImportErrors;

        $showCreateModal = $canEdit
            && $errorBag !== null
            && ! $request->session()->hasOldInput('_method')
            && ! $hasImportErrors;

        $currentYear = (int) now()->format('Y');
        $importAnioOptions = [];
        for ($year = $currentYear + 1; $year >= $currentYear - 10; $year--) {
            $importAnioOptions[] = ['value' => (string) $year, 'label' => (string) $year];
        }

        $importMesOptions = [];
        foreach (ClienteInternoDatatableService::MESES as $value => $label) {
            $importMesOptions[] = ['value' => (string) $value, 'label' => $label];
        }

        return view('areas.gestion_humana.cliente_interno.solicitudes', [
            'subTabs' => $this->getClienteInternoSubTabs('solicitudes'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'filterAnioOptions' => $options['anios'],
            'filterMesOptions' => $options['meses'],
            'filterEstadoOptions' => $options['estados'],
            'tipoSolicitudOptions' => $tipoOptions,
            'estadoFormOptions' => $estadoFormOptions,
            'importAnioOptions' => $importAnioOptions,
            'importMesOptions' => $importMesOptions,
            'datatableUrl' => route('gestion-humana.cliente-interno.solicitudes.datatable', $activeQuery),
            'exportUrl' => route('gestion-humana.cliente-interno.solicitudes.export', $activeQuery),
            'storeUrl' => route('gestion-humana.cliente-interno.solicitudes.store'),
            'importTemplateUrl' => route('gestion-humana.cliente-interno.solicitudes.import-template'),
            'importUrl' => route('gestion-humana.cliente-interno.solicitudes.import'),
            'periodCountUrl' => route('gestion-humana.cliente-interno.solicitudes.period-count'),
            'showCreateModal' => $showCreateModal,
            'showImportModal' => $showImportModal,
        ]);
    }

    public function solicitudesDatatable(Request $request): JsonResponse
    {
        abort_unless($this->clienteInternoAccess->canViewSolicitudes(auth()->user()), 403);

        return $this->datatableService->respond(
            $request,
            $this->solicitudFiltersFromRequest($request),
            $this->clienteInternoAccess->canEditSolicitudes(auth()->user()),
        );
    }

    public function solicitudesExport(Request $request): StreamedResponse
    {
        abort_unless($this->clienteInternoAccess->canViewSolicitudes(auth()->user()), 403);

        $filters = $this->solicitudFiltersFromRequest($request);
        $rows = $this->datatableService->filteredQuery($filters)->get();

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'solicitudes_excel',
            metadata: [
                'row_count' => $rows->count(),
                'filters' => array_filter(
                    $filters,
                    static fn ($value): bool => $value !== null && $value !== '',
                ),
            ],
            userId: (int) auth()->id(),
        );

        return ClienteInternoExport::downloadCollection($rows);
    }

    public function storeSolicitud(StoreClienteInternoSolicitudRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $fechaSolicitud = Carbon::parse($validated['fecha_solicitud'])->startOfDay();
        $fechaRespuesta = isset($validated['fecha_respuesta']) && $validated['fecha_respuesta'] !== null
            ? Carbon::parse($validated['fecha_respuesta'])->startOfDay()
            : null;

        $requestDiasProvided = array_key_exists('dias_respuesta', $validated)
            && $validated['dias_respuesta'] !== null;
        $requestDias = $requestDiasProvided ? (int) $validated['dias_respuesta'] : null;

        [$diasRespuesta, $manual] = $this->businessDaysService->resolveForCreate(
            $fechaSolicitud,
            $fechaRespuesta,
            $requestDias,
            $requestDiasProvided,
        );

        $solicitud = ClienteInternoSolicitud::query()->create([
            'fecha_solicitud' => $fechaSolicitud->toDateString(),
            'anio' => (int) $fechaSolicitud->format('Y'),
            'mes' => (int) $fechaSolicitud->format('n'),
            'nombre_apellidos' => $validated['nombre_apellidos'],
            'cedula' => $validated['cedula'],
            'correo_electronico' => $validated['correo_electronico'] ?? null,
            'tipo_solicitud_id' => (int) $validated['tipo_solicitud_id'],
            'fecha_respuesta' => $fechaRespuesta?->toDateString(),
            'estado_id' => $validated['estado_id'] ?? null,
            'novedad' => $validated['novedad'] ?? null,
            'dias_respuesta' => $diasRespuesta,
            'dias_respuesta_manual' => $manual,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'cliente_interno_solicitud',
            action: 'create',
            metadata: [
                'cliente_interno_solicitud_id' => $solicitud->id,
                'cedula' => $solicitud->cedula,
            ],
            model: $solicitud,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cliente-interno.solicitudes')
            ->with('status', 'Solicitud creada correctamente.');
    }

    public function updateSolicitud(
        UpdateClienteInternoSolicitudRequest $request,
        ClienteInternoSolicitud $clienteInternoSolicitud,
    ): RedirectResponse {
        $validated = $request->validated();
        $fechaSolicitud = Carbon::parse($validated['fecha_solicitud'])->startOfDay();
        $fechaRespuesta = isset($validated['fecha_respuesta']) && $validated['fecha_respuesta'] !== null
            ? Carbon::parse($validated['fecha_respuesta'])->startOfDay()
            : null;

        // dias_respuesta_touched: la UI lo marca al editar el input (evita falso override).
        $requestDiasProvided = array_key_exists('dias_respuesta', $validated)
            && $validated['dias_respuesta'] !== null;
        $requestDias = $requestDiasProvided ? (int) $validated['dias_respuesta'] : null;

        [$diasRespuesta, $manual] = $this->businessDaysService->resolveForUpdate(
            $fechaSolicitud,
            $fechaRespuesta,
            $requestDias,
            $requestDiasProvided,
            $request->boolean('dias_respuesta_touched'),
            (bool) $clienteInternoSolicitud->dias_respuesta_manual,
            $clienteInternoSolicitud->dias_respuesta,
            $request->boolean('recalcular_dias'),
        );

        $before = $clienteInternoSolicitud->only([
            'fecha_solicitud',
            'anio',
            'mes',
            'nombre_apellidos',
            'cedula',
            'correo_electronico',
            'tipo_solicitud_id',
            'fecha_respuesta',
            'estado_id',
            'novedad',
            'dias_respuesta',
            'dias_respuesta_manual',
        ]);

        $clienteInternoSolicitud->update([
            'fecha_solicitud' => $fechaSolicitud->toDateString(),
            'anio' => (int) $fechaSolicitud->format('Y'),
            'mes' => (int) $fechaSolicitud->format('n'),
            'nombre_apellidos' => $validated['nombre_apellidos'],
            'cedula' => $validated['cedula'],
            'correo_electronico' => $validated['correo_electronico'] ?? null,
            'tipo_solicitud_id' => (int) $validated['tipo_solicitud_id'],
            'fecha_respuesta' => $fechaRespuesta?->toDateString(),
            'estado_id' => $validated['estado_id'] ?? null,
            'novedad' => $validated['novedad'] ?? null,
            'dias_respuesta' => $diasRespuesta,
            'dias_respuesta_manual' => $manual,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'cliente_interno_solicitud',
            action: 'update',
            model: $clienteInternoSolicitud,
            before: $before,
            after: $clienteInternoSolicitud->only(array_keys($before)),
            metadata: [
                'cliente_interno_solicitud_id' => $clienteInternoSolicitud->id,
                'cedula' => $clienteInternoSolicitud->cedula,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cliente-interno.solicitudes', $request->query())
            ->with('status', 'Solicitud actualizada correctamente.');
    }

    public function destroySolicitud(ClienteInternoSolicitud $clienteInternoSolicitud): RedirectResponse
    {
        abort_unless($this->clienteInternoAccess->canEditSolicitudes(auth()->user()), 403);

        $metadata = [
            'cliente_interno_solicitud_id' => $clienteInternoSolicitud->id,
            'cedula' => $clienteInternoSolicitud->cedula,
        ];

        $clienteInternoSolicitud->delete();

        $this->auditLogService->logEvent(
            eventType: 'cliente_interno_solicitud',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cliente-interno.solicitudes')
            ->with('status', 'Solicitud eliminada.');
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->clienteInternoAccess->canEditSolicitudes(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function periodCount(Request $request): JsonResponse
    {
        abort_unless($this->clienteInternoAccess->canEditSolicitudes(auth()->user()), 403);

        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $anio = (int) $validated['anio'];
        $mes = (int) $validated['mes'];
        $mesLabel = ClienteInternoDatatableService::MESES[$mes] ?? (string) $mes;

        return response()->json([
            'anio' => $anio,
            'mes' => $mes,
            'count' => $this->importService->countInPeriod($anio, $mes),
            'label' => "{$mesLabel} {$anio}",
        ]);
    }

    public function import(ImportClienteInternoSolicitudesRequest $request): RedirectResponse
    {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        $anio = (int) $request->validated('anio');
        $mes = (int) $request->validated('mes');

        try {
            $stats = $this->importService->import($path, $anio, $mes, $request->user()?->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        $mesLabel = ClienteInternoDatatableService::MESES[$mes] ?? (string) $mes;
        $message = sprintf(
            'Importación completada (%s %d): %d registro(s) cargado(s); se eliminaron %d del periodo; fuera de periodo: %d; filas vacías omitidas: %d.',
            $mesLabel,
            $anio,
            $stats['imported'],
            $stats['deleted_in_period'],
            $stats['accepted_outside_period'],
            $stats['skipped_empty'],
        );

        if (($stats['tipos_created'] ?? 0) > 0 || ($stats['estados_created'] ?? 0) > 0) {
            $message .= sprintf(
                ' Catálogo: %d tipo(s) y %d estado(s) nuevos creados desde el archivo.',
                (int) ($stats['tipos_created'] ?? 0),
                (int) ($stats['estados_created'] ?? 0),
            );
        }

        return redirect()
            ->route('gestion-humana.cliente-interno.solicitudes')
            ->with('status', $message);
    }

    public function catalogos(Request $request): View
    {
        abort_unless($this->clienteInternoAccess->canEditParameters(auth()->user()), 403);

        // Sin ?catalog= muestra el tablero de tarjetas (Estados + Tipos de solicitud).
        $catalogQuery = $request->query('catalog');
        $activeCatalog = is_string($catalogQuery) && $this->catalogService->isManagedType($catalogQuery)
            ? $catalogQuery
            : null;

        return view('areas.gestion_humana.cliente_interno.catalogos', [
            'subTabs' => $this->getClienteInternoSubTabs('catalogos'),
            'catalogs' => $this->catalogService->catalogsForAdmin(),
            'activeCatalog' => $activeCatalog,
        ]);
    }

    public function storeCatalog(StoreClienteInternoCatalogItemRequest $request, string $type): RedirectResponse
    {
        abort_unless($this->clienteInternoAccess->canEditParameters(auth()->user()), 403);
        abort_unless($this->catalogService->isManagedType($type), 404);

        $modelClass = $this->catalogService->modelClassFor($type);

        $item = $modelClass::query()->create([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($request->input('sort_order') ?? 0),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'cliente_interno_catalog',
            action: 'create',
            metadata: [
                'catalog_type' => $type,
                'code' => $item->code,
                'catalog_item_id' => $item->id,
            ],
            model: $item,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cliente-interno.catalogos', ['catalog' => $type])
            ->with('status', 'Catálogo actualizado correctamente.');
    }

    public function updateCatalog(UpdateClienteInternoCatalogItemRequest $request, string $type, int $item): RedirectResponse
    {
        abort_unless($this->clienteInternoAccess->canEditParameters(auth()->user()), 403);

        $model = $this->catalogService->findItemOrFail($type, $item);
        $before = $model->only(['code', 'name', 'is_active', 'sort_order']);

        $model->update([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->input('sort_order') ?? $model->sort_order ?? 0),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'cliente_interno_catalog',
            action: 'update',
            model: $model,
            before: $before,
            after: $model->only(array_keys($before)),
            metadata: [
                'catalog_type' => $type,
                'code' => $model->code,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cliente-interno.catalogos', ['catalog' => $type])
            ->with('status', 'Registro de catálogo actualizado.');
    }

    public function destroyCatalog(string $type, int $item): RedirectResponse
    {
        abort_unless($this->clienteInternoAccess->canEditParameters(auth()->user()), 403);

        $model = $this->catalogService->findItemOrFail($type, $item);

        if ($this->catalogService->hasBusinessReferences($type, $model)) {
            return redirect()
                ->route('gestion-humana.cliente-interno.catalogos', ['catalog' => $type])
                ->with('error', 'No se puede eliminar: hay solicitudes que usan este valor. Desactívelo en su lugar.');
        }

        $metadata = [
            'catalog_type' => $type,
            'code' => $model->code,
            'catalog_item_id' => $model->id,
        ];

        $model->delete();

        $this->auditLogService->logEvent(
            eventType: 'cliente_interno_catalog',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cliente-interno.catalogos', ['catalog' => $type])
            ->with('status', 'Registro eliminado del catálogo.');
    }

    /**
     * @return array{
     *     anio: string,
     *     mes: string,
     *     estado_id: string,
     *     cedula: string,
     *     nombre: string,
     * }
     */
    private function solicitudFiltersFromRequest(Request $request): array
    {
        return [
            'anio' => trim((string) $request->query('anio', '')),
            'mes' => trim((string) $request->query('mes', '')),
            'estado_id' => trim((string) $request->query('estado_id', '')),
            'cedula' => trim((string) $request->query('cedula', '')),
            'nombre' => trim((string) $request->query('nombre', '')),
        ];
    }

    /**
     * @return array{anio: string, mes: string}
     */
    private function dashboardFiltersFromRequest(Request $request): array
    {
        $anio = $this->dashboardAnioFromRequest($request);
        $mesRaw = trim((string) $request->query('mes', ''));
        $mes = ($mesRaw !== '' && ctype_digit($mesRaw) && (int) $mesRaw >= 1 && (int) $mesRaw <= 12)
            ? (string) ((int) $mesRaw)
            : '';

        return [
            'anio' => $anio !== null ? (string) $anio : '',
            'mes' => $mes,
        ];
    }

    private function dashboardAnioFromRequest(Request $request): ?int
    {
        $raw = trim((string) $request->query('anio', ''));
        if ($raw === '' || ! ctype_digit($raw)) {
            return null;
        }

        $anio = (int) $raw;

        return ($anio >= 2000 && $anio <= 2100) ? $anio : null;
    }
}
