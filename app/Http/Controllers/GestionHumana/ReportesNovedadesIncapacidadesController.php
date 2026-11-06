<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\ReportesNovedadesIncapacidadesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ReportesNovedades\ReviewIncapacidadRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\StoreIncapacidadAdjuntosRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\StoreIncapacidadRequest;
use App\Http\Requests\GestionHumana\ReportesNovedades\UpdateIncapacidadRequest;
use App\Models\ReportesNovedadesIncapacidad;
use App\Models\ReportesNovedadesIncapacidadAdjunto;
use App\Services\Access\ReportesNovedadesAccessService;
use App\Services\GestionHumana\ReportesNovedadesAuditLogService;
use App\Services\GestionHumana\ReportesNovedadesHistorialService;
use App\Services\GestionHumana\ReportesNovedadesIncapacidadAdjuntoService;
use App\Services\GestionHumana\ReportesNovedadesIncapacidadesDatatableService;
use App\Support\DisplayDate;
use App\Support\ReportesNovedadesPeriodFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    ];

    public function __construct(
        private readonly ReportesNovedadesAccessService $access,
        private readonly ReportesNovedadesIncapacidadesDatatableService $datatableService,
        private readonly ReportesNovedadesAuditLogService $auditLogService,
        private readonly ReportesNovedadesHistorialService $historialService,
        private readonly ReportesNovedadesIncapacidadesExport $export,
        private readonly ReportesNovedadesIncapacidadAdjuntoService $adjuntoService,
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
            'datatableUrl' => route('gestion-humana.reportes-novedades.incapacidades.datatable', ReportesNovedadesPeriodFilter::toQueryParams($filters)),
            'exportUrl' => route('gestion-humana.reportes-novedades.incapacidades.export', ReportesNovedadesPeriodFilter::toQueryParams($filters)),
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
        $this->syncDiasEntrega($row);

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
        $this->syncDiasEntrega($incapacidad);

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

    public function attachments(ReportesNovedadesIncapacidad $incapacidad): JsonResponse
    {
        abort_unless($this->access->canAccessSheet(auth()->user(), self::SHEET), 403);

        return response()->json([
            'can_edit' => $this->access->canEdit(auth()->user(), self::SHEET),
            'data' => $this->attachmentPayload($incapacidad),
        ]);
    }

    public function storeAttachments(StoreIncapacidadAdjuntosRequest $request, ReportesNovedadesIncapacidad $incapacidad): JsonResponse
    {
        /** @var list<UploadedFile> $files */
        $files = array_values(array_filter(
            (array) $request->file('attachments', []),
            fn ($file): bool => $file instanceof UploadedFile,
        ));

        $stored = $this->adjuntoService->storeMany($incapacidad, $files, (int) auth()->id());

        $this->auditLogService->logEvent(
            eventType: 'incapacidades_adjunto',
            action: 'create',
            metadata: [
                'sheet' => self::SHEET,
                'id' => $incapacidad->id,
                'document_number' => $incapacidad->document_number,
                'files' => array_map(
                    fn (ReportesNovedadesIncapacidadAdjunto $adjunto): string => $adjunto->original_name,
                    $stored,
                ),
            ],
            model: $incapacidad,
            userId: (int) auth()->id(),
        );

        return response()->json([
            'message' => 'Archivos adjuntados correctamente.',
            'can_edit' => true,
            'data' => $this->attachmentPayload($incapacidad),
        ]);
    }

    public function downloadAttachment(
        ReportesNovedadesIncapacidad $incapacidad,
        ReportesNovedadesIncapacidadAdjunto $adjunto,
    ): StreamedResponse {
        abort_unless($this->access->canAccessSheet(auth()->user(), self::SHEET), 403);
        $this->assertAdjuntoBelongsTo($incapacidad, $adjunto);

        $disk = (string) config('reportes_novedades.incapacidades_adjuntos.disk', 'local');

        abort_unless(Storage::disk($disk)->exists($adjunto->stored_path), 404);

        return Storage::disk($disk)->download($adjunto->stored_path, $adjunto->original_name);
    }

    public function destroyAttachment(
        ReportesNovedadesIncapacidad $incapacidad,
        ReportesNovedadesIncapacidadAdjunto $adjunto,
    ): JsonResponse {
        abort_unless($this->access->canEdit(auth()->user(), self::SHEET), 403);
        $this->assertAdjuntoBelongsTo($incapacidad, $adjunto);

        $originalName = $adjunto->original_name;
        $adjunto->delete();

        $this->auditLogService->logEvent(
            eventType: 'incapacidades_adjunto',
            action: 'delete',
            metadata: [
                'sheet' => self::SHEET,
                'id' => $incapacidad->id,
                'document_number' => $incapacidad->document_number,
                'file' => $originalName,
            ],
            model: $incapacidad,
            userId: (int) auth()->id(),
        );

        return response()->json([
            'message' => 'Adjunto eliminado.',
            'can_edit' => true,
            'data' => $this->attachmentPayload($incapacidad),
        ]);
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
        $filters = $rowId === null ? $this->historialService->filtersFromRequest($request) : [];

        return response()->json([
            'data' => $this->historialService->forSheet(
                self::SHEET,
                ReportesNovedadesIncapacidad::class,
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
    /**
     * @return list<array{id: int, original_name: string, size_label: string, uploaded_at: string, uploaded_by: string, download_url: string, delete_url: ?string}>
     */
    private function attachmentPayload(ReportesNovedadesIncapacidad $incapacidad): array
    {
        $canEdit = $this->access->canEdit(auth()->user(), self::SHEET);

        return $incapacidad->adjuntos()
            ->with('uploader:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (ReportesNovedadesIncapacidadAdjunto $adjunto): array => [
                'id' => $adjunto->id,
                'original_name' => $adjunto->original_name,
                'size_label' => $adjunto->sizeLabel(),
                'uploaded_at' => DisplayDate::dateTime($adjunto->created_at),
                'uploaded_by' => $adjunto->uploader?->name ?: '—',
                'download_url' => route('gestion-humana.reportes-novedades.incapacidades.attachments.download', [$incapacidad, $adjunto]),
                'delete_url' => $canEdit
                    ? route('gestion-humana.reportes-novedades.incapacidades.attachments.destroy', [$incapacidad, $adjunto])
                    : null,
            ])
            ->all();
    }

    private function assertAdjuntoBelongsTo(
        ReportesNovedadesIncapacidad $incapacidad,
        ReportesNovedadesIncapacidadAdjunto $adjunto,
    ): void {
        abort_unless($adjunto->incapacidad_id === $incapacidad->id, 404);
    }

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

    private function syncDiasEntrega(ReportesNovedadesIncapacidad $row): void
    {
        $row->forceFill([
            'dias_entrega' => $row->diasEntregaCalculados(),
        ])->saveQuietly();
    }
}
