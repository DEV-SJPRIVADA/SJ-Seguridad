<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\ReportesNovedadesPermisosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ReportesNovedades\ReviewPermisoRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\StorePermisoRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\UpdatePermisoRequest;
use App\Models\ReportesNovedadesPermiso;
use App\Services\Access\ReportesNovedadesAccessService;
use App\Services\GestionHumana\ReportesNovedadesAuditLogService;
use App\Services\GestionHumana\ReportesNovedadesHistorialService;
use App\Services\GestionHumana\ReportesNovedadesPermisosDatatableService;
use App\Support\ReportesNovedadesPeriodFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesNovedadesPermisosController extends Controller
{
    private const SHEET = 'permisos';

    private const GH_FIELDS = [
        'document_number',
        'employee_name',
        'tipo',
        'cargo',
        'novedad',
        'dias_novedad',
        'fecha_inicio',
        'fecha_fin',
        'marca_gh',
    ];

    public function __construct(
        private readonly ReportesNovedadesAccessService $access,
        private readonly ReportesNovedadesPermisosDatatableService $datatableService,
        private readonly ReportesNovedadesAuditLogService $auditLogService,
        private readonly ReportesNovedadesHistorialService $historialService,
        private readonly ReportesNovedadesPermisosExport $export,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->access->canAccessSheet(auth()->user(), self::SHEET), 403);

        $filters = $this->filtersFromRequest($request);
        $canEdit = $this->access->canEdit(auth()->user(), self::SHEET);
        $canReview = $this->access->canReview(auth()->user(), self::SHEET);
        $canExport = $this->access->canExport(auth()->user(), self::SHEET);

        return view('areas.gestion_humana.reportes_novedades.permisos', [
            'subTabs' => $this->subTabs(),
            'activeSheet' => self::SHEET,
            'canEdit' => $canEdit,
            'canReview' => $canReview,
            'canExport' => $canExport,
            'filters' => $filters,
            'datatableUrl' => route('gestion-humana.reportes-novedades.permisos.datatable', ReportesNovedadesPeriodFilter::toQueryParams($filters)),
            'exportUrl' => route('gestion-humana.reportes-novedades.permisos.export', ReportesNovedadesPeriodFilter::toQueryParams($filters)),
            'historialUrl' => route('gestion-humana.reportes-novedades.permisos.historial'),
            'lookupUrl' => route('gestion-humana.reportes-novedades.lookup'),
            'storeUrl' => route('gestion-humana.reportes-novedades.permisos.store'),
            'novedadOptions' => $this->catalogOptions('permisos_novedad'),
            'showCreateModal' => $canEdit && $request->session()->get('errors') !== null
                && ! $request->session()->hasOldInput('_method'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->access->canAccessSheet(auth()->user(), self::SHEET), 403);

        return $this->datatableService->respond(
            $request,
            $this->filtersFromRequest($request),
            $this->access->canEdit(auth()->user(), self::SHEET),
            $this->access->canReview(auth()->user(), self::SHEET),
        );
    }

    public function store(StorePermisoRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $row = ReportesNovedadesPermiso::query()->create([
            ...$payload,
            'marca_gh' => (bool) ($payload['marca_gh'] ?? false),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'permisos_novedad',
            action: 'create',
            metadata: [
                'sheet' => self::SHEET,
                'id' => $row->id,
                'document_number' => $row->document_number,
            ],
            model: $row,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.permisos')
            ->with('status', 'Registro de permisos creado correctamente.');
    }

    public function update(UpdatePermisoRequest $request, ReportesNovedadesPermiso $permiso): RedirectResponse
    {
        $before = $permiso->only(self::GH_FIELDS);
        $payload = $request->validated();

        $permiso->update([
            ...$payload,
            'marca_gh' => (bool) ($payload['marca_gh'] ?? false),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'permisos_novedad',
            action: 'update',
            model: $permiso,
            before: $before,
            after: $permiso->only(self::GH_FIELDS),
            metadata: [
                'sheet' => self::SHEET,
                'id' => $permiso->id,
                'document_number' => $permiso->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.permisos', $request->query())
            ->with('status', 'Registro de permisos actualizado correctamente.');
    }

    public function review(ReviewPermisoRequest $request, ReportesNovedadesPermiso $permiso): RedirectResponse
    {
        $before = $permiso->only(['observacion_nomina']);
        $payload = $request->validated();

        $permiso->update([
            ...$payload,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'permisos_novedad',
            action: 'review',
            model: $permiso,
            before: $before,
            after: $permiso->only(['observacion_nomina']),
            metadata: [
                'sheet' => self::SHEET,
                'id' => $permiso->id,
                'document_number' => $permiso->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.permisos', $request->query())
            ->with('status', 'Observación de Nómina actualizada.');
    }

    public function destroy(ReportesNovedadesPermiso $permiso): RedirectResponse
    {
        abort_unless($this->access->canEdit(auth()->user(), self::SHEET), 403);

        $metadata = [
            'sheet' => self::SHEET,
            'id' => $permiso->id,
            'document_number' => $permiso->document_number,
        ];

        $permiso->delete();

        $this->auditLogService->logEvent(
            eventType: 'permisos_novedad',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.permisos')
            ->with('status', 'Registro de permisos eliminado.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($this->access->canExport(auth()->user(), self::SHEET), 403);

        $filters = $this->filtersFromRequest($request);
        $rows = $this->datatableService->filteredQuery($filters)->get();

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'permisos_excel',
            metadata: [
                'sheet' => self::SHEET,
                'rows' => $rows->count(),
                'filters' => $filters,
            ],
            userId: (int) auth()->id(),
        );

        return $this->export->download($rows, $this->datatableService->exportColumns());
    }

    public function historial(Request $request): JsonResponse
    {
        abort_unless($this->access->canAccessSheet(auth()->user(), self::SHEET), 403);

        $rowId = $request->query('id');
        $rowId = $rowId !== null && $rowId !== '' ? (int) $rowId : null;
        $filters = $rowId === null ? $this->historialService->filtersFromRequest($request) : [];

        return response()->json([
            'data' => $this->historialService->forSheet(
                self::SHEET,
                ReportesNovedadesPermiso::class,
                $rowId,
                filters: $filters,
            ),
        ]);
    }

    /**
     * @return array{
     *     q: string,
     *     mes: string,
     *     quincena: string,
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     period_active: bool,
     *     period_desde: string|null,
     *     period_hasta: string|null
     * }
     */
    private function filtersFromRequest(Request $request): array
    {
        return ReportesNovedadesPeriodFilter::resolveFromRequest($request);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function catalogOptions(string $key): array
    {
        return collect(config('reportes_novedades.catalogos.'.$key, []))
            ->map(fn (string $value): array => ['value' => $value, 'label' => $value])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    private function subTabs(): array
    {
        $visible = $this->access->visibleTabsFor(auth()->user());
        $labels = config('access.reportes_novedades_tabs', []);

        return collect($visible)
            ->map(fn (string $key): array => [
                'label' => (string) ($labels[$key] ?? $key),
                'url' => route('gestion-humana.reportes-novedades.'.$key),
                'active' => $key === self::SHEET,
            ])
            ->values()
            ->all();
    }
}
