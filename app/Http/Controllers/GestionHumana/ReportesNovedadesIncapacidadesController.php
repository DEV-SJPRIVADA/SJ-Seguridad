<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\ReportesNovedadesIncapacidadesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ReportesNovedades\ReviewIncapacidadRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\StoreIncapacidadRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\UpdateIncapacidadRequest;
use App\Models\ReportesNovedadesIncapacidad;
use App\Services\Access\ReportesNovedadesAccessService;
use App\Services\GestionHumana\ReportesNovedadesAuditLogService;
use App\Services\GestionHumana\ReportesNovedadesHistorialService;
use App\Services\GestionHumana\ReportesNovedadesIncapacidadesDatatableService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesNovedadesIncapacidadesController extends Controller
{
    private const SHEET = 'incapacidades';

    private const GH_FIELDS = [
        'document_number',
        'employee_name',
        'cargo',
        'destino',
        'tipo_incapacidad',
        'dias',
        'fecha_inicio',
        'fecha_fin',
        'fecha_recepcion',
        'fecha_devolucion',
        'observacion_devolucion',
        'fecha_registro_control_roll',
        'fecha_envio_final',
        'novedad_control_roll',
        'extemporanea',
        'observaciones',
    ];

    private const NOMINA_FIELDS = [
        'observacion_nomina',
        'dias_entrega',
    ];

    public function __construct(
        private readonly ReportesNovedadesAccessService $access,
        private readonly ReportesNovedadesIncapacidadesDatatableService $datatableService,
        private readonly ReportesNovedadesAuditLogService $auditLogService,
        private readonly ReportesNovedadesHistorialService $historialService,
        private readonly ReportesNovedadesIncapacidadesExport $export,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->access->canAccessSheet(auth()->user(), self::SHEET), 403);

        $filters = $this->filtersFromRequest($request);
        $canEdit = $this->access->canEdit(auth()->user(), self::SHEET);
        $canReview = $this->access->canReview(auth()->user(), self::SHEET);
        $canExport = $this->access->canExport(auth()->user(), self::SHEET);

        return view('areas.gestion_humana.reportes_novedades.incapacidades', [
            'subTabs' => $this->subTabs(),
            'activeSheet' => self::SHEET,
            'canEdit' => $canEdit,
            'canReview' => $canReview,
            'canExport' => $canExport,
            'filters' => $filters,
            'datatableUrl' => route('gestion-humana.reportes-novedades.incapacidades.datatable', array_filter($filters)),
            'exportUrl' => route('gestion-humana.reportes-novedades.incapacidades.export', array_filter($filters)),
            'historialUrl' => route('gestion-humana.reportes-novedades.incapacidades.historial'),
            'lookupUrl' => route('gestion-humana.reportes-novedades.lookup'),
            'storeUrl' => route('gestion-humana.reportes-novedades.incapacidades.store'),
            'tipoOptions' => $this->catalogOptions('incapacidades_tipo'),
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

    public function store(StoreIncapacidadRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $row = ReportesNovedadesIncapacidad::query()->create([
            ...$payload,
            'extemporanea' => (bool) ($payload['extemporanea'] ?? false),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'incapacidades_novedad',
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
            ->route('gestion-humana.reportes-novedades.incapacidades')
            ->with('status', 'Registro de incapacidades creado correctamente.');
    }

    public function update(UpdateIncapacidadRequest $request, ReportesNovedadesIncapacidad $incapacidad): RedirectResponse
    {
        $before = $incapacidad->only(self::GH_FIELDS);
        $payload = $request->validated();

        $incapacidad->update([
            ...$payload,
            'extemporanea' => (bool) ($payload['extemporanea'] ?? false),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'incapacidades_novedad',
            action: 'update',
            model: $incapacidad,
            before: $before,
            after: $incapacidad->only(self::GH_FIELDS),
            metadata: [
                'sheet' => self::SHEET,
                'id' => $incapacidad->id,
                'document_number' => $incapacidad->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.incapacidades', $request->query())
            ->with('status', 'Registro de incapacidades actualizado correctamente.');
    }

    public function review(ReviewIncapacidadRequest $request, ReportesNovedadesIncapacidad $incapacidad): RedirectResponse
    {
        $before = $incapacidad->only(self::NOMINA_FIELDS);
        $payload = $request->validated();

        $incapacidad->update([
            ...$payload,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'incapacidades_novedad',
            action: 'review',
            model: $incapacidad,
            before: $before,
            after: $incapacidad->only(self::NOMINA_FIELDS),
            metadata: [
                'sheet' => self::SHEET,
                'id' => $incapacidad->id,
                'document_number' => $incapacidad->document_number,
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.incapacidades', $request->query())
            ->with('status', 'Revisión de Nómina actualizada.');
    }

    public function destroy(ReportesNovedadesIncapacidad $incapacidad): RedirectResponse
    {
        abort_unless($this->access->canEdit(auth()->user(), self::SHEET), 403);

        $metadata = [
            'sheet' => self::SHEET,
            'id' => $incapacidad->id,
            'document_number' => $incapacidad->document_number,
        ];

        $incapacidad->delete();

        $this->auditLogService->logEvent(
            eventType: 'incapacidades_novedad',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.reportes-novedades.incapacidades')
            ->with('status', 'Registro de incapacidades eliminado.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($this->access->canExport(auth()->user(), self::SHEET), 403);

        $filters = $this->filtersFromRequest($request);
        $rows = $this->datatableService->filteredQuery($filters)->get();

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'incapacidades_excel',
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

        return response()->json([
            'data' => $this->historialService->forSheet(
                self::SHEET,
                ReportesNovedadesIncapacidad::class,
                $rowId,
            ),
        ]);
    }

    /**
     * @return array{q: string, fecha_desde: string, fecha_hasta: string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'fecha_desde' => trim((string) $request->query('fecha_desde', '')),
            'fecha_hasta' => trim((string) $request->query('fecha_hasta', '')),
        ];
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
