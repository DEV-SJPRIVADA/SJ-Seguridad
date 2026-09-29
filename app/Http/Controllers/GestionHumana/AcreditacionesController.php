<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\AcreditacionesImportTemplateExport;
use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\Acreditaciones\AcreditacionValidacionesDatatableRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\AcreditacionValidacionesExportRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\BulkUpdateAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\GenerateAcreditacionExportApoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\ImportAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\ImportAcreditacionReporteDiarioRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\PreviewAcreditacionExportApoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\RunAcreditacionValidacionesRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\StoreAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\StoreAcreditacionCargoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\UpdateAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\UpdateAcreditacionCargoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\UpdateAcreditacionExportApoParamsRequest;
use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\AcreditacionExportApoSetting;
use App\Models\AcreditacionReporteDiarioFila;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Services\Access\AcreditacionesAccessService;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\AcreditacionAcreditadoDatatableService;
use App\Services\GestionHumana\AcreditacionAcreditadoListService;
use App\Services\GestionHumana\AcreditacionCargoCatalogService;
use App\Services\GestionHumana\AcreditacionDashboardService;
use App\Services\GestionHumana\AcreditacionesAuditLogService;
use App\Services\GestionHumana\AcreditacionEstadoCalculator;
use App\Services\GestionHumana\AcreditacionExportApoGenerateService;
use App\Services\GestionHumana\AcreditacionExportApoPreviewService;
use App\Services\GestionHumana\AcreditacionExportApoRowResolver;
use App\Services\GestionHumana\AcreditacionImportService;
use App\Services\GestionHumana\AcreditacionReporteDiarioDatatableService;
use App\Services\GestionHumana\AcreditacionReporteDiarioImportService;
use App\Services\GestionHumana\AcreditacionReporteDiarioListService;
use App\Services\GestionHumana\AcreditacionValidacionesDatatableService;
use App\Services\GestionHumana\AcreditacionValidacionesExportService;
use App\Services\GestionHumana\AcreditacionValidacionesGateService;
use App\Services\GestionHumana\AcreditacionValidacionesResultStore;
use App\Services\GestionHumana\AcreditacionValidacionesRunnerService;
use App\Support\DocumentNumberListParser;
use App\Traits\HasAcreditacionesTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcreditacionesController extends Controller
{
    use HasAcreditacionesTabs;

    public function __construct(
        private readonly AcreditacionesAccessService $acreditacionesAccess,
        private readonly AcreditacionCargoCatalogService $catalogService,
        private readonly AcreditacionesAuditLogService $auditLogService,
        private readonly AcreditacionAcreditadoListService $acreditadoListService,
        private readonly AcreditacionAcreditadoDatatableService $acreditadoDatatableService,
        private readonly AcreditacionEstadoCalculator $estadoCalculator,
        private readonly AcreditacionImportService $importService,
        private readonly AcreditacionesImportTemplateExport $importTemplateExport,
        private readonly AcreditacionReporteDiarioImportService $reporteDiarioImportService,
        private readonly AcreditacionReporteDiarioListService $reporteDiarioListService,
        private readonly AcreditacionReporteDiarioDatatableService $reporteDiarioDatatableService,
        private readonly AcreditacionValidacionesGateService $validacionesGateService,
        private readonly AcreditacionValidacionesRunnerService $validacionesRunnerService,
        private readonly AcreditacionValidacionesResultStore $validacionesResultStore,
        private readonly AcreditacionValidacionesDatatableService $validacionesDatatableService,
        private readonly AcreditacionValidacionesExportService $validacionesExportService,
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly AcreditacionExportApoPreviewService $exportApoPreviewService,
        private readonly AcreditacionExportApoGenerateService $exportApoGenerateService,
        private readonly AcreditacionDashboardService $dashboardService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return redirect()->route('gestion-humana.acreditaciones.acreditados', $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $payload = $this->dashboardService->metrics($filters);

        $filterCargoApoOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $this->dashboardService->cargoApoOptions(),
        );

        $filterFichaEstadoOptions = [
            ['value' => EmployeeFichaProfile::STATUS_ACTIVO, 'label' => 'Activos en ficha'],
            ['value' => EmployeeFichaProfile::STATUS_DESVINCULADO, 'label' => 'Desvinculados'],
            ['value' => 'todos', 'label' => 'Todos (ficha)'],
        ];

        return view('areas.gestion_humana.acreditaciones.dashboard', [
            'subTabs' => $this->getAcreditacionesSubTabs('dashboard'),
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.acreditaciones.dashboard.metrics'),
            'filterCargoApoOptions' => $filterCargoApoOptions,
            'filterFichaEstadoOptions' => $filterFichaEstadoOptions,
            'yearOptions' => $this->dashboardService->yearOptions(),
        ]);
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return response()->json(
            $this->dashboardService->metrics($this->dashboardFiltersFromRequest($request))
        );
    }

    public function acreditados(Request $request): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        $canEdit = $this->acreditacionesAccess->canEdit(auth()->user());
        $filters = $this->acreditadoFiltersFromRequest($request);

        /** @var array<string, string> $estadoLabels */
        $estadoLabels = config('acreditaciones.estados', []);

        $filterEstadoOptions = array_merge(
            [['value' => 'todos', 'label' => 'Todos']],
            collect($estadoLabels)
                ->map(fn (string $label, string $code): array => [
                    'value' => $code,
                    'label' => $label,
                ])
                ->values()
                ->all(),
        );

        /** @var array<string, string> $renovacionLabels */
        $renovacionLabels = config('acreditaciones.renovaciones', []);

        $renovacionOptions = collect($renovacionLabels)
            ->map(fn (string $label, string $code): array => [
                'value' => $code,
                'label' => $label,
            ])
            ->values()
            ->all();

        $filterRenovacionOptions = array_merge(
            [['value' => 'todos', 'label' => 'Todos']],
            $renovacionOptions,
        );

        $cargoApoOptions = AcreditacionCargo::query()
            ->active()
            ->orderBy('cargo_apo')
            ->get(['cargo_apo'])
            ->pluck('cargo_apo')
            ->unique(fn (string $apo): string => mb_strtolower(trim($apo)))
            ->values()
            ->map(fn (string $apo): array => [
                'value' => $apo,
                'label' => $apo,
            ])
            ->all();

        $filterCargoApoOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $cargoApoOptions,
        );

        $filterCargoFichaOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            PayrollCatalogItem::query()
                ->ofType('position')
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name'])
                ->pluck('name')
                ->map(fn (mixed $name): string => trim((string) $name))
                ->filter(fn (string $name): bool => $name !== '')
                ->unique(fn (string $name): string => mb_strtolower($name))
                ->values()
                ->map(fn (string $name): array => [
                    'value' => $name,
                    'label' => $name,
                ])
                ->all(),
        );

        $filterFichaEstadoOptions = [
            ['value' => EmployeeFichaProfile::STATUS_ACTIVO, 'label' => 'Activos en ficha'],
            ['value' => EmployeeFichaProfile::STATUS_DESVINCULADO, 'label' => 'Desvinculados'],
            ['value' => 'todos', 'label' => 'Todos (ficha)'],
        ];

        return view('areas.gestion_humana.acreditaciones.acreditados', [
            'subTabs' => $this->getAcreditacionesSubTabs('acreditados'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'filterEstadoOptions' => $filterEstadoOptions,
            'filterRenovacionOptions' => $filterRenovacionOptions,
            'filterFichaEstadoOptions' => $filterFichaEstadoOptions,
            'filterCargoApoOptions' => $filterCargoApoOptions,
            'filterCargoFichaOptions' => $filterCargoFichaOptions,
            'cargoApoOptions' => $cargoApoOptions,
            'renovacionOptions' => $renovacionOptions,
            'lookupUrl' => route('gestion-humana.acreditaciones.acreditados.lookup'),
            'datatableUrl' => route(
                'gestion-humana.acreditaciones.acreditados.datatable',
                $this->activeAcreditadoFilterQuery($filters),
            ),
            'bulkSelectableUrl' => route(
                'gestion-humana.acreditaciones.acreditados.bulk-selectable',
                $this->activeAcreditadoFilterQuery($filters),
            ),
            'bulkUpdateUrl' => route('gestion-humana.acreditaciones.acreditados.bulk-update'),
            'exportApoUrl' => route('gestion-humana.acreditaciones.export-apo'),
            'exportUrl' => route(
                'gestion-humana.acreditaciones.acreditados.export',
                $this->activeAcreditadoFilterQuery($filters),
            ),
            'importTemplateUrl' => route('gestion-humana.acreditaciones.acreditados.import-template'),
            'importUrl' => route('gestion-humana.acreditaciones.acreditados.import'),
            'activeFilterQuery' => $this->activeAcreditadoFilterQuery($filters),
        ]);
    }

    public function acreditadosDatatable(Request $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return $this->acreditadoDatatableService->respond(
            $request,
            $this->acreditadoFiltersFromRequest($request),
            $this->acreditacionesAccess->canEdit(auth()->user()),
        );
    }

    public function bulkSelectable(Request $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $rows = $this->acreditadoDatatableService->bulkSelectableRows(
            $this->acreditadoFiltersFromRequest($request),
        );

        return response()->json(['data' => $rows]);
    }

    public function bulkUpdate(BulkUpdateAcreditacionAcreditadoRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = array_values(array_map('intval', $request->validated('ids')));
        $observacionesInput = $request->validated('observaciones');
        $fechaSolicitudInput = $request->validated('fecha_solicitud');
        $renovacionInput = $request->validated('renovacion');

        $applyObservaciones = is_string($observacionesInput) && trim($observacionesInput) !== '';
        $applyFechaSolicitud = filled($fechaSolicitudInput);
        $applyRenovacion = filled($renovacionInput);

        $observaciones = $applyObservaciones ? trim((string) $observacionesInput) : null;
        $fechaSolicitud = $applyFechaSolicitud
            ? Carbon::parse((string) $fechaSolicitudInput)->toDateString()
            : null;
        $renovacion = $applyRenovacion ? (string) $renovacionInput : null;

        $updatedCount = 0;

        DB::transaction(function () use (
            $ids,
            $applyObservaciones,
            $applyFechaSolicitud,
            $applyRenovacion,
            $observaciones,
            $fechaSolicitud,
            $renovacion,
            &$updatedCount,
        ): void {
            $rows = AcreditacionAcreditado::query()
                ->whereIn('id', $ids)
                ->get();

            foreach ($rows as $row) {
                if ($applyObservaciones) {
                    $row->observaciones = $observaciones;
                }

                if ($applyFechaSolicitud) {
                    $row->fecha_solicitud = $fechaSolicitud;
                }

                if ($applyRenovacion) {
                    $row->renovacion = $renovacion;
                }

                $row->estado = $this->estadoCalculator->calculate(
                    $row->fecha_solicitud,
                    $row->vigencia_acr,
                );
                $row->updated_by = auth()->id();
                $row->save();
                $updatedCount++;
            }

            $this->auditLogService->logEvent(
                eventType: 'acreditacion_acreditado',
                action: 'bulk_update',
                metadata: [
                    'requested_ids' => $ids,
                    'updated_count' => $updatedCount,
                    'applied_observaciones' => $applyObservaciones,
                    'applied_fecha_solicitud' => $applyFechaSolicitud,
                    'applied_renovacion' => $applyRenovacion,
                ],
                userId: (int) auth()->id(),
            );
        });

        $filters = $this->acreditadoFiltersFromRequest($request);

        if ($updatedCount === 0) {
            return $this->redirectAfterBulkAcreditadoUpdate(
                $request,
                $filters,
                error: 'No se actualizó ningún registro.',
            );
        }

        $message = $updatedCount === 1
            ? '1 acreditado actualizado.'
            : "{$updatedCount} acreditados actualizados.";

        return $this->redirectAfterBulkAcreditadoUpdate(
            $request,
            $filters,
            status: $message,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function redirectAfterBulkAcreditadoUpdate(
        Request $request,
        array $filters,
        ?string $status = null,
        ?string $error = null,
    ): RedirectResponse {
        if ((string) $request->input('_return_context') === 'export_apo') {
            $returnIds = collect($request->input('_return_ids', $request->input('ids', [])))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->take((int) config('acreditaciones.limits.bulk_max_ids', 500))
                ->values()
                ->all();

            $redirect = redirect()->route(
                'gestion-humana.acreditaciones.export-apo',
                $returnIds === [] ? [] : ['ids' => $returnIds],
            );

            if ($error !== null) {
                return $redirect->with('error', $error);
            }

            return $redirect->with(
                'status',
                ($status ?? 'Acreditados actualizados.').' Se revalidaron los candidatos en Export Apo.',
            );
        }

        $redirect = redirect()->route(
            'gestion-humana.acreditaciones.acreditados',
            $this->activeAcreditadoFilterQuery($filters),
        );

        if ($error !== null) {
            return $redirect->with('error', $error);
        }

        return $redirect->with('status', $status ?? 'Acreditados actualizados.');
    }

    public function acreditadosLookup(Request $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $cedula = trim((string) $request->query('cedula', ''));

        if ($cedula === '') {
            return response()->json(['found' => false]);
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->first(['id', 'document_number', 'full_name', 'position_name']);

        if ($profile === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'document_number' => $profile->document_number,
            'full_name' => $profile->full_name,
            'cargo' => trim((string) ($profile->position_name ?? '')),
            'employee_ficha_profile_id' => $profile->id,
        ]);
    }

    public function storeAcreditado(StoreAcreditacionAcreditadoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->acreditadoPayloadFromValidated(
            $validated,
            $request->resolvedFullName(),
            $request->resolvedCargo(),
        );

        $acreditado = AcreditacionAcreditado::query()->create([
            ...$payload,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'acreditacion_acreditado',
            action: 'create',
            metadata: [
                'acreditacion_acreditado_id' => $acreditado->id,
                'document_number' => $acreditado->document_number,
                'cargo_apo' => $acreditado->cargo_apo,
                'estado' => $acreditado->estado,
            ],
            model: $acreditado,
            userId: (int) auth()->id(),
        );

        return $this->redirectAfterAcreditadoMutation(
            $request,
            'Acreditado creado correctamente. Vuelva a ejecutar validaciones para actualizar las colas.',
            'Acreditado creado correctamente.',
        );
    }

    public function updateAcreditado(
        UpdateAcreditacionAcreditadoRequest $request,
        AcreditacionAcreditado $acreditacionAcreditado,
    ): RedirectResponse {
        $before = $acreditacionAcreditado->only([
            'document_number',
            'full_name',
            'cargo',
            'cargo_apo',
            'vigencia_acr',
            'fecha_solicitud',
            'estado',
            'renovacion',
            'observaciones',
        ]);

        $payload = $this->acreditadoPayloadFromValidated(
            $request->validated(),
            $request->resolvedFullName(),
            $request->resolvedCargo(),
        );

        $acreditacionAcreditado->update([
            ...$payload,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'acreditacion_acreditado',
            action: 'update',
            model: $acreditacionAcreditado,
            before: $before,
            after: $acreditacionAcreditado->only(array_keys($before)),
            userId: (int) auth()->id(),
        );

        return $this->redirectAfterAcreditadoMutation(
            $request,
            'Acreditado actualizado correctamente. Vuelva a ejecutar validaciones para actualizar las colas.',
            'Acreditado actualizado correctamente.',
            $this->activeAcreditadoFilterQuery($this->acreditadoFiltersFromRequest($request)),
        );
    }

    public function destroyAcreditado(AcreditacionAcreditado $acreditacionAcreditado): RedirectResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $metadata = [
            'acreditacion_acreditado_id' => $acreditacionAcreditado->id,
            'document_number' => $acreditacionAcreditado->document_number,
            'cargo_apo' => $acreditacionAcreditado->cargo_apo,
        ];

        $acreditacionAcreditado->delete();

        $this->auditLogService->logEvent(
            eventType: 'acreditacion_acreditado',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.acreditados')
            ->with('status', 'Acreditado eliminado.');
    }

    public function exportAcreditados(Request $request): StreamedResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        $filters = $this->acreditadoFiltersFromRequest($request);
        $rows = $this->acreditadoListService->all($filters);

        $columns = [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'full_name', 'label' => 'NOMBRE COMPLETO'],
            ['key' => 'cargo', 'label' => 'CARGO'],
            ['key' => 'cargo_apo', 'label' => 'CARGO APO'],
            ['key' => 'vigencia_acr', 'label' => 'VIGEN.ACR'],
            ['key' => 'estado', 'label' => 'ESTADO'],
            ['key' => 'renovacion', 'label' => 'RENOVACIONES'],
            ['key' => 'fecha_solicitud', 'label' => 'FECHA SOLICITUD'],
            ['key' => 'observaciones', 'label' => 'OBSERVACIONES'],
        ];

        $data = $rows->map(fn (AcreditacionAcreditado $row): array => [
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'cargo' => $row->cargo,
            'cargo_apo' => $row->cargo_apo,
            'vigencia_acr' => optional($row->vigencia_acr)?->format('Y-m-d'),
            'estado' => $this->estadoCalculator->estadoLabel((string) $row->estado),
            'renovacion' => $row->renovacionLabel() === '—' ? null : $row->renovacionLabel(),
            'fecha_solicitud' => optional($row->fecha_solicitud)?->format('Y-m-d'),
            'observaciones' => $row->observaciones,
        ]);

        return (new BaseExport(
            $data,
            $columns,
            'acreditados_'.now()->format('Y-m-d').'.xlsx',
            'Acreditados — '.config('app.name'),
        ))->download();
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function importAcreditados(ImportAcreditacionAcreditadoRequest $request): RedirectResponse
    {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path, $request->user()?->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        $message = sprintf(
            'Importacion finalizada: %d nuevos, %d actualizados.',
            $stats['imported'],
            $stats['updated'],
        );

        if ($stats['empty_rows'] > 0) {
            $message .= sprintf(' %d filas vacias ignoradas.', $stats['empty_rows']);
        }

        if ($stats['skipped'] > 0) {
            $message .= sprintf(' %d filas con error.', $stats['skipped']);
        }

        $token = null;
        if ($stats['failures'] !== []) {
            $token = Str::uuid()->toString();
            Cache::put('acreditaciones_import_report_'.$token, $stats['failures'], now()->addHour());
        }

        $this->auditLogService->logEvent(
            eventType: 'acreditacion_import',
            action: 'process',
            metadata: [
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'empty_rows' => $stats['empty_rows'],
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.acreditados')
            ->with('status', $message)
            ->with('import_done', true)
            ->with('import_result', [
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'empty_rows' => $stats['empty_rows'],
                'failures_count' => $stats['skipped'],
                'report_token' => $token,
            ])
            ->with('import_failures', array_slice($stats['failures'], 0, 50))
            ->with('import_report_token', $token);
    }

    public function downloadImportReport(string $token): StreamedResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        /** @var list<array<string, mixed>>|null $failures */
        $failures = Cache::get('acreditaciones_import_report_'.$token);

        if (! is_array($failures)) {
            abort(404);
        }

        $columns = [
            ['key' => 'row', 'label' => 'Fila'],
            ['key' => 'identifier', 'label' => 'Cedula'],
            ['key' => 'severity', 'label' => 'Severidad'],
            ['key' => 'reason', 'label' => 'Motivo'],
        ];

        $data = collect($failures)->map(fn (array $failure): array => [
            'row' => $failure['row'] ?? '',
            'identifier' => $failure['identifier'] ?? '',
            'severity' => $failure['severity'] ?? '',
            'reason' => $failure['reason'] ?? '',
        ]);

        return (new BaseExport(
            $data,
            $columns,
            'reporte_importacion_acreditados_'.now()->format('Y-m-d_His').'.xlsx',
            'Errores importacion Acreditados',
        ))->download();
    }

    public function reporteDiario(Request $request): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        $canEdit = $this->acreditacionesAccess->canEdit(auth()->user());
        $filters = $this->reporteDiarioFiltersFromRequest($request);

        /** @var array<string, string> $origenLabels */
        $origenLabels = config('acreditaciones.reporte_diario.origenes', []);

        $filterOrigenOptions = array_merge(
            [['value' => 'todos', 'label' => 'Todos']],
            collect($origenLabels)
                ->map(fn (string $label, string $code): array => [
                    'value' => $code,
                    'label' => $label,
                ])
                ->values()
                ->all(),
        );

        $today = Carbon::now(config('app.timezone'))->toDateString();

        return view('areas.gestion_humana.acreditaciones.reporte-diario', [
            'subTabs' => $this->getAcreditacionesSubTabs('reporte_diario'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'filterOrigenOptions' => $filterOrigenOptions,
            'today' => $today,
            'datatableUrl' => route(
                'gestion-humana.acreditaciones.reporte-diario.datatable',
                $this->activeReporteDiarioFilterQuery($filters),
            ),
            'exportUrl' => route(
                'gestion-humana.acreditaciones.reporte-diario.export',
                $this->activeReporteDiarioFilterQuery($filters),
            ),
            'importUrl' => route('gestion-humana.acreditaciones.reporte-diario.import'),
            'cargasUrl' => route('gestion-humana.acreditaciones.reporte-diario.cargas'),
            'activeFilterQuery' => $this->activeReporteDiarioFilterQuery($filters),
        ]);
    }

    public function reporteDiarioDatatable(Request $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return $this->reporteDiarioDatatableService->respond(
            $request,
            $this->reporteDiarioFiltersFromRequest($request),
        );
    }

    public function exportReporteDiario(Request $request): StreamedResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        $filters = $this->reporteDiarioFiltersFromRequest($request);
        $rows = $this->reporteDiarioListService->all($filters);

        $columns = [
            ['key' => 'origen', 'label' => 'Origen'],
            ['key' => 'apellido1', 'label' => 'Apellido1'],
            ['key' => 'apellido2', 'label' => 'Apellido2'],
            ['key' => 'nombre1', 'label' => 'Nombre1'],
            ['key' => 'nombre2', 'label' => 'Nombre2'],
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'document_number', 'label' => 'IdNum'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'estado_apo', 'label' => 'Estado (APO)'],
            ['key' => 'vigencia_acr', 'label' => 'Vigen.Acr'],
        ];

        $data = $rows->map(fn (AcreditacionReporteDiarioFila $row): array => [
            'origen' => $row->origenLabel(),
            'apellido1' => $row->apellido1,
            'apellido2' => $row->apellido2,
            'nombre1' => $row->nombre1,
            'nombre2' => $row->nombre2,
            'full_name' => $row->full_name,
            'document_number' => $row->document_number,
            'cargo' => $row->cargo,
            'estado_apo' => $row->resolvedEstadoApo(),
            'vigencia_acr' => optional($row->vigencia_acr)?->format('Y-m-d'),
        ]);

        $fecha = $filters['fecha_reporte'];

        return (new BaseExport(
            $data,
            $columns,
            'reporte_diario_apo_'.$fecha.'_'.now()->format('His').'.xlsx',
            'Reporte Diario APO — '.$fecha.' — '.config('app.name'),
        ))->download();
    }

    public function importReporteDiario(ImportAcreditacionReporteDiarioRequest $request): RedirectResponse
    {
        try {
            $result = $this->reporteDiarioImportService->import([
                'fecha_reporte' => (string) $request->validated('fecha_reporte'),
                'file_proceso' => $request->file('file_proceso'),
                'file_acreditado' => $request->file('file_acreditado'),
                'confirm_replace' => $request->boolean('confirm_replace'),
            ], $request->user()?->id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['file_proceso' => $e->getMessage()]);
        }

        $carga = $result['carga'];
        $fecha = optional($carga->fecha_reporte)?->format('Y-m-d')
            ?? (string) $request->validated('fecha_reporte');

        $parts = [];
        foreach ($result['by_origen'] as $origen => $stats) {
            $label = config('acreditaciones.reporte_diario.origenes.'.$origen, $origen);
            $parts[] = sprintf('%s: %d ok, %d fallo(s)', $label, $stats['ok'], $stats['fail']);
        }

        $message = ($result['replaced'] ? 'Reemplazo' : 'Carga').' finalizada ('.$fecha.'). '.implode(' · ', $parts);

        if ($result['empty_rows'] > 0) {
            $message .= sprintf(' %d filas vacías ignoradas.', $result['empty_rows']);
        }

        $token = null;
        if ($result['failures'] !== []) {
            $token = Str::uuid()->toString();
            Cache::put('acreditaciones_reporte_diario_import_report_'.$token, $result['failures'], now()->addHour());
        }

        $this->auditLogService->logEvent(
            eventType: 'acreditacion_reporte_diario_carga',
            action: 'imported',
            metadata: [
                'carga_id' => $carga->id,
                'fecha_reporte' => $fecha,
                'origins' => $result['origins'],
                'by_origen' => $result['by_origen'],
                'replaced' => $result['replaced'],
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'empty_rows' => $result['empty_rows'],
            ],
            model: $carga,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.reporte-diario', ['fecha_reporte' => $fecha])
            ->with('status', $message)
            ->with('import_done', true)
            ->with('import_result', [
                'imported' => $result['imported'],
                'updated' => 0,
                'skipped' => $result['skipped'],
                'empty_rows' => $result['empty_rows'],
                'failures_count' => $result['skipped'],
                'report_token' => $token,
                'replaced' => $result['replaced'],
                'by_origen' => $result['by_origen'],
            ])
            ->with('import_failures', array_slice($result['failures'], 0, 50))
            ->with('import_report_token', $token);
    }

    public function downloadReporteDiarioImportReport(string $token): StreamedResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        /** @var list<array<string, mixed>>|null $failures */
        $failures = Cache::get('acreditaciones_reporte_diario_import_report_'.$token);

        if (! is_array($failures)) {
            abort(404);
        }

        $columns = [
            ['key' => 'row', 'label' => 'Fila'],
            ['key' => 'identifier', 'label' => 'IdNum'],
            ['key' => 'severity', 'label' => 'Severidad'],
            ['key' => 'reason', 'label' => 'Motivo'],
        ];

        $data = collect($failures)->map(fn (array $failure): array => [
            'row' => $failure['row'] ?? '',
            'identifier' => $failure['identifier'] ?? '',
            'severity' => $failure['severity'] ?? '',
            'reason' => $failure['reason'] ?? '',
        ]);

        return (new BaseExport(
            $data,
            $columns,
            'reporte_fallos_reporte_diario_'.now()->format('Y-m-d_His').'.xlsx',
            'Errores importación Reporte Diario APO',
        ))->download();
    }

    public function reporteDiarioCargas(): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        $cargas = $this->reporteDiarioListService->cargasList()->map(function ($carga): array {
            return [
                'id' => $carga->id,
                'fecha_reporte' => optional($carga->fecha_reporte)?->format('Y-m-d'),
                'proceso_file_name' => $carga->proceso_file_name,
                'proceso_rows_ok' => (int) $carga->proceso_rows_ok,
                'proceso_rows_fail' => (int) $carga->proceso_rows_fail,
                'proceso_loaded_at' => optional($carga->proceso_loaded_at)?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'proceso_loaded_by' => $carga->procesoLoadedBy?->name,
                'acreditado_file_name' => $carga->acreditado_file_name,
                'acreditado_rows_ok' => (int) $carga->acreditado_rows_ok,
                'acreditado_rows_fail' => (int) $carga->acreditado_rows_fail,
                'acreditado_loaded_at' => optional($carga->acreditado_loaded_at)?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'acreditado_loaded_by' => $carga->acreditadoLoadedBy?->name,
                'view_url' => route('gestion-humana.acreditaciones.reporte-diario', [
                    'fecha_reporte' => optional($carga->fecha_reporte)?->format('Y-m-d'),
                ]),
            ];
        })->values()->all();

        return response()->json(['data' => $cargas]);
    }

    public function validaciones(Request $request): View
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $today = Carbon::now(config('app.timezone'))->toDateString();
        $fecha = $this->reporteDiarioListService->resolveFecha($request->input('fecha_reporte'));
        $gate = $this->validacionesGateService->evaluate($fecha);
        $reporteDiarioUrl = route('gestion-humana.acreditaciones.reporte-diario', [
            'fecha_reporte' => $fecha,
        ]);

        $runToken = trim((string) $request->input('run_token', ''));
        $runPayload = null;
        $runExpired = false;

        if ($runToken !== '') {
            $runPayload = $this->validacionesResultStore->get(
                (int) auth()->id(),
                $fecha,
                $runToken,
            );
            $runExpired = $runPayload === null;
        }

        /** @var array<string, string> $colaLabels */
        $colaLabels = config('acreditaciones.validaciones.colas', []);

        $actionsColumn = [
            'data' => 'actions',
            'title' => 'Acciones',
            'orderable' => false,
            'searchable' => false,
        ];

        $selectColumn = [
            'data' => 'select',
            'title' => '',
            'orderable' => false,
            'searchable' => false,
            'className' => 'cursos-registros-page__select-col',
        ];

        $colaDefs = [
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => [
                'label' => $colaLabels[AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION] ?? 'Ficha activa sin acreditación',
                'columns' => [
                    $selectColumn,
                    ['data' => 'document_number', 'title' => 'Cédula'],
                    ['data' => 'full_name', 'title' => 'Nombre'],
                    ['data' => 'cargo', 'title' => 'Cargo Ficha'],
                    ['data' => 'personal_tipo', 'title' => 'Tipo'],
                    $actionsColumn,
                ],
            ],
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => [
                'label' => $colaLabels[AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE] ?? 'Ausente del reporte',
                'columns' => [
                    $selectColumn,
                    ['data' => 'document_number', 'title' => 'Cédula'],
                    ['data' => 'full_name', 'title' => 'Nombre'],
                    ['data' => 'cargo', 'title' => 'Cargo Ficha'],
                    ['data' => 'cargo_apo', 'title' => 'CARGO APO'],
                    ['data' => 'estado', 'title' => 'Estado'],
                    $actionsColumn,
                ],
            ],
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => [
                'label' => $colaLabels[AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO] ?? 'EN PROCESO ya acreditado APO',
                'columns' => [
                    $selectColumn,
                    ['data' => 'document_number', 'title' => 'Cédula'],
                    ['data' => 'full_name', 'title' => 'Nombre'],
                    ['data' => 'cargo_apo', 'title' => 'CARGO APO'],
                    ['data' => 'estado', 'title' => 'Estado'],
                    ['data' => 'fecha_solicitud', 'title' => 'Fecha solicitud'],
                    ['data' => 'vigencia_apo', 'title' => 'VIGEN.ACR APO'],
                    $actionsColumn,
                ],
            ],
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => [
                'label' => $colaLabels[AcreditacionValidacionesResultStore::COLA_VENCIDAS] ?? 'Vencidas / por vencer',
                'columns' => [
                    $selectColumn,
                    ['data' => 'document_number', 'title' => 'Cédula'],
                    ['data' => 'full_name', 'title' => 'Nombre'],
                    ['data' => 'cargo_apo', 'title' => 'CARGO APO'],
                    ['data' => 'estado', 'title' => 'Estado'],
                    ['data' => 'vigencia_acr', 'title' => 'Vigencia ACR'],
                    $actionsColumn,
                ],
            ],
        ];

        /** @var array<string, string> $estadoLabels */
        $estadoLabels = config('acreditaciones.estados', []);
        $filterEstadoOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            collect($estadoLabels)
                ->map(fn (string $label, string $code): array => [
                    'value' => $code,
                    'label' => $label,
                ])
                ->values()
                ->all(),
        );

        $filterPersonalTipoOptions = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'OPERATIVO', 'label' => 'Operativo'],
            ['value' => 'ADMINISTRATIVO', 'label' => 'Administrativo'],
        ];

        $filterCargoFichaOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            PayrollCatalogItem::query()
                ->ofType('position')
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name'])
                ->pluck('name')
                ->map(fn (mixed $name): string => trim((string) $name))
                ->filter(fn (string $name): bool => $name !== '')
                ->unique(fn (string $name): string => mb_strtolower($name))
                ->values()
                ->map(fn (string $name): array => [
                    'value' => $name,
                    'label' => $name,
                ])
                ->all(),
        );

        /** @var array<string, string> $renovacionLabels */
        $renovacionLabels = config('acreditaciones.renovaciones', []);
        $renovacionOptions = collect($renovacionLabels)
            ->map(fn (string $label, string $code): array => [
                'value' => $code,
                'label' => $label,
            ])
            ->values()
            ->all();

        $cargoApoOptions = AcreditacionCargo::query()
            ->active()
            ->orderBy('cargo_apo')
            ->get(['cargo_apo'])
            ->pluck('cargo_apo')
            ->unique(fn (string $apo): string => mb_strtolower(trim($apo)))
            ->values()
            ->map(fn (string $apo): array => [
                'value' => $apo,
                'label' => $apo,
            ])
            ->all();

        $filterCargoApoOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $cargoApoOptions,
        );

        $effectiveRunToken = $runPayload['run_token'] ?? null;
        $exportQueryBase = [
            'fecha_reporte' => $fecha,
            'run_token' => $effectiveRunToken,
        ];

        $exportUrls = [];
        foreach (array_keys($colaDefs) as $colaCode) {
            $exportUrls[$colaCode] = $effectiveRunToken
                ? route('gestion-humana.acreditaciones.validaciones.export', [
                    ...$exportQueryBase,
                    'cola' => $colaCode,
                ])
                : null;
        }

        $exportConsolidatedUrl = $effectiveRunToken
            ? route('gestion-humana.acreditaciones.validaciones.export-consolidated', $exportQueryBase)
            : null;

        $showNuevoModal = (string) old('_return_context') === 'validaciones'
            && $request->session()->has('errors');

        return view('areas.gestion_humana.acreditaciones.validaciones', [
            'subTabs' => $this->getAcreditacionesSubTabs('validaciones'),
            'canEdit' => true,
            'today' => $today,
            'fecha' => $fecha,
            'gate' => $gate,
            'gateOk' => $gate['ok'],
            'reporteDiarioUrl' => $reporteDiarioUrl,
            'runToken' => $effectiveRunToken,
            'runCounts' => $runPayload['counts'] ?? null,
            'runExpired' => $runExpired,
            'colaLabels' => $colaLabels,
            'colaDefs' => $colaDefs,
            'defaultCola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
            'datatableUrl' => route('gestion-humana.acreditaciones.validaciones.datatable'),
            'bulkSelectableUrl' => route('gestion-humana.acreditaciones.validaciones.bulk-selectable'),
            'exportApoUrl' => route('gestion-humana.acreditaciones.export-apo'),
            'runUrl' => route('gestion-humana.acreditaciones.validaciones.run'),
            'exportUrls' => $exportUrls,
            'exportConsolidatedUrl' => $exportConsolidatedUrl,
            'lookupUrl' => route('gestion-humana.acreditaciones.acreditados.lookup'),
            'cargoApoOptions' => $cargoApoOptions,
            'filterCargoApoOptions' => $filterCargoApoOptions,
            'filterEstadoOptions' => $filterEstadoOptions,
            'filterPersonalTipoOptions' => $filterPersonalTipoOptions,
            'filterCargoFichaOptions' => $filterCargoFichaOptions,
            'renovacionOptions' => $renovacionOptions,
            'showNuevoModal' => $showNuevoModal,
            'validacionesReturn' => [
                'fecha_reporte' => $fecha,
                'run_token' => $effectiveRunToken,
            ],
        ]);
    }

    public function validacionesRun(RunAcreditacionValidacionesRequest $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $fecha = $request->fechaReporte();
        $gate = $this->validacionesGateService->evaluate($fecha);

        if (! $gate['ok']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $gate['message'],
                    'gate' => $gate,
                ], 422);
            }

            return redirect()
                ->route('gestion-humana.acreditaciones.validaciones', [
                    'fecha_reporte' => $fecha,
                ])
                ->with('error', $gate['message']);
        }

        $result = $this->validacionesRunnerService->run($fecha);
        $stored = $this->validacionesResultStore->put(
            (int) auth()->id(),
            $fecha,
            $result,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'run_token' => $stored['run_token'],
                'fecha_reporte' => $fecha,
                'counts' => $stored['counts'],
            ]);
        }

        return redirect()
            ->route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
                'run_token' => $stored['run_token'],
            ])
            ->with('status', 'Validaciones ejecutadas. Revise las cuatro colas abajo.');
    }

    public function validacionesDatatable(AcreditacionValidacionesDatatableRequest $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $canOpenFicha = $this->fichaEmpleadosAccess->canManage(auth()->user());

        return $this->validacionesDatatableService->respond(
            $request,
            (int) auth()->id(),
            $request->fechaReporte(),
            $request->runToken(),
            $request->cola(),
            $canOpenFicha,
            canEdit: true,
        );
    }

    public function validacionesBulkSelectable(AcreditacionValidacionesDatatableRequest $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $rows = $this->validacionesDatatableService->bulkSelectableRows(
            $request,
            (int) auth()->id(),
            $request->fechaReporte(),
            $request->runToken(),
            $request->cola(),
        );

        return response()->json(['data' => $rows]);
    }

    public function exportValidaciones(AcreditacionValidacionesExportRequest $request): StreamedResponse|RedirectResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $result = $this->validacionesExportService->exportCola(
            (int) auth()->id(),
            $request->fechaReporte(),
            $request->runToken(),
            $request->cola(),
            $request->filters(),
        );

        if (is_array($result)) {
            return redirect()
                ->route('gestion-humana.acreditaciones.validaciones', [
                    'fecha_reporte' => $request->fechaReporte(),
                ])
                ->with('error', $result['error']);
        }

        return $result;
    }

    public function exportValidacionesConsolidado(AcreditacionValidacionesExportRequest $request): StreamedResponse|RedirectResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $result = $this->validacionesExportService->exportConsolidated(
            (int) auth()->id(),
            $request->fechaReporte(),
            $request->runToken(),
        );

        if (is_array($result)) {
            return redirect()
                ->route('gestion-humana.acreditaciones.validaciones', [
                    'fecha_reporte' => $request->fechaReporte(),
                ])
                ->with('error', $result['error']);
        }

        return $result;
    }

    public function exportApo(Request $request): View
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $vigenciaPolicies = collect(config('acreditaciones.export_apo.vigencia_policies', []))
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();

        $settings = AcreditacionExportApoSetting::singleton();
        $maxIds = (int) config('acreditaciones.limits.bulk_max_ids', 500);
        $autoValidateIds = collect($request->input('ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take($maxIds)
            ->values()
            ->all();

        /** @var array<string, string> $renovacionLabels */
        $renovacionLabels = config('acreditaciones.renovaciones', []);
        $renovacionOptions = collect($renovacionLabels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();

        $cargoApoOptions = AcreditacionCargo::query()
            ->active()
            ->ordered()
            ->get(['cargo_apo'])
            ->map(fn (AcreditacionCargo $row): array => [
                'value' => (string) $row->cargo_apo,
                'label' => (string) $row->cargo_apo,
            ])
            ->unique('value')
            ->values()
            ->all();

        return view('areas.gestion_humana.acreditaciones.export-apo', [
            'subTabs' => $this->getAcreditacionesSubTabs('export_apo'),
            'vigenciaPolicyOptions' => $vigenciaPolicies,
            'defaultVigenciaPolicy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
            'previewUrl' => route('gestion-humana.acreditaciones.export-apo.preview'),
            'generateUrl' => route('gestion-humana.acreditaciones.export-apo.generate'),
            'exportApoSettings' => $settings,
            'autoValidateIds' => $autoValidateIds,
            'cargoApoOptions' => $cargoApoOptions,
            'renovacionOptions' => $renovacionOptions,
            'lookupUrl' => route('gestion-humana.acreditaciones.acreditados.lookup'),
            'bulkUpdateUrl' => route('gestion-humana.acreditaciones.acreditados.bulk-update'),
        ]);
    }

    public function exportApoPreview(PreviewAcreditacionExportApoRequest $request): JsonResponse
    {
        $preview = $request->previewAll()
            ? $this->exportApoPreviewService->previewAll($request->vigenciaPolicy())
            : $this->exportApoPreviewService->preview(
                $request->ids(),
                $request->vigenciaPolicy(),
            );

        return response()->json($preview);
    }

    public function exportApoGenerate(GenerateAcreditacionExportApoRequest $request): StreamedResponse|RedirectResponse
    {
        try {
            $result = $this->exportApoGenerateService->generate(
                $request->ids(),
                $request->vigenciaPolicy(),
                $request->includeNovedades(),
                (int) auth()->id(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('gestion-humana.acreditaciones.export-apo')
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first() ?: 'No se pudo generar el archivo Export Apo.');
        }

        $run = $result['run'];

        $this->auditLogService->logEvent(
            eventType: 'export_apo_generate',
            action: 'generate',
            reason: null,
            metadata: [
                'file_name' => $run->file_name,
                'export_date' => optional($run->export_date)?->format('Y-m-d'),
                'seq' => $run->seq,
                'vigencia_policy' => $run->vigencia_policy,
                'include_novedades' => $run->include_novedades,
                'rows_selected' => $run->rows_selected,
                'rows_ok' => $run->rows_ok,
                'rows_novedad' => $run->rows_novedad,
                'rows_blocked' => $run->rows_blocked,
                'rows_exported' => $run->rows_exported,
            ],
            model: $run,
            userId: (int) auth()->id(),
        );

        return $result['response'];
    }

    public function catalogo(): View
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $cargos = AcreditacionCargo::query()
            ->ordered()
            ->get();

        return view('areas.gestion_humana.acreditaciones.catalogo', [
            'subTabs' => $this->getAcreditacionesSubTabs('catalogo'),
            'cargos' => $cargos,
            'catalogService' => $this->catalogService,
            'exportApoSettings' => AcreditacionExportApoSetting::singleton(),
            'exportApoLabels' => config('acreditaciones.export_apo.labels', []),
        ]);
    }

    public function updateExportApoParams(UpdateAcreditacionExportApoParamsRequest $request): RedirectResponse
    {
        $settings = AcreditacionExportApoSetting::singleton();

        $fieldKeys = [
            'nit',
            'razon_social',
            'tipo_documento',
            'tipo_establecimiento',
            'telefono_r',
            'direccion_r',
            'direccion_p',
            'departamento',
            'ciudad',
            'educacion_bm',
            'educacion_s',
            'discapacidad',
        ];

        $before = $settings->only($fieldKeys);
        $validated = $request->validated();

        $settings->fill($validated);
        $settings->updated_by = (int) auth()->id();
        $settings->save();

        $this->auditLogService->logModelChange(
            eventType: 'export_apo_settings',
            action: 'update',
            model: $settings,
            before: $before,
            after: $settings->only($fieldKeys),
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.catalogo')
            ->with('status', 'Parámetros Export Apo actualizados correctamente.');
    }

    public function storeCatalogo(StoreAcreditacionCargoRequest $request): RedirectResponse
    {
        $cargo = AcreditacionCargo::query()->create([
            'cargo_manager' => $request->string('cargo_manager')->toString(),
            'cargo_apo' => $request->string('cargo_apo')->toString(),
            'cargo_informe' => $request->string('cargo_informe')->toString(),
            'cargo_acreditacion' => $request->string('cargo_acreditacion')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'acreditacion_cargo',
            action: 'create',
            metadata: [
                'acreditacion_cargo_id' => $cargo->id,
                'cargo_manager' => $cargo->cargo_manager,
                'cargo_apo' => $cargo->cargo_apo,
            ],
            model: $cargo,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.catalogo')
            ->with('status', 'Cargo de acreditación creado correctamente.');
    }

    public function updateCatalogo(UpdateAcreditacionCargoRequest $request, AcreditacionCargo $acreditacionCargo): RedirectResponse
    {
        $before = $acreditacionCargo->only([
            'cargo_manager',
            'cargo_apo',
            'cargo_informe',
            'cargo_acreditacion',
            'is_active',
            'sort_order',
        ]);

        $acreditacionCargo->update([
            'cargo_manager' => $request->string('cargo_manager')->toString(),
            'cargo_apo' => $request->string('cargo_apo')->toString(),
            'cargo_informe' => $request->string('cargo_informe')->toString(),
            'cargo_acreditacion' => $request->string('cargo_acreditacion')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'acreditacion_cargo',
            action: 'update',
            model: $acreditacionCargo,
            before: $before,
            after: $acreditacionCargo->only(array_keys($before)),
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.catalogo')
            ->with('status', 'Cargo de acreditación actualizado correctamente.');
    }

    public function destroyCatalogo(AcreditacionCargo $acreditacionCargo): RedirectResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        if (! $this->catalogService->canDelete($acreditacionCargo)) {
            return redirect()
                ->route('gestion-humana.acreditaciones.catalogo')
                ->with('error', $this->catalogService->deletionBlockReason($acreditacionCargo));
        }

        $metadata = [
            'acreditacion_cargo_id' => $acreditacionCargo->id,
            'cargo_manager' => $acreditacionCargo->cargo_manager,
            'cargo_apo' => $acreditacionCargo->cargo_apo,
        ];

        $acreditacionCargo->delete();

        $this->auditLogService->logEvent(
            eventType: 'acreditacion_cargo',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.acreditaciones.catalogo')
            ->with('status', 'Cargo de acreditación eliminado.');
    }

    /**
     * @param  array<string, mixed>  $acreditadosQuery
     */
    private function redirectAfterAcreditadoMutation(
        Request $request,
        string $validacionesStatus,
        string $acreditadosStatus,
        array $acreditadosQuery = [],
    ): RedirectResponse {
        if ((string) $request->input('_return_context') === 'validaciones') {
            $fecha = trim((string) $request->input('_return_fecha_reporte', ''));
            $token = trim((string) $request->input('_return_run_token', ''));

            if ($fecha === '') {
                $fecha = Carbon::now(config('app.timezone'))->toDateString();
            }

            $query = ['fecha_reporte' => $fecha];
            if ($token !== '') {
                $query['run_token'] = $token;
            }

            return redirect()
                ->route('gestion-humana.acreditaciones.validaciones', $query)
                ->with('status', $validacionesStatus);
        }

        if ((string) $request->input('_return_context') === 'export_apo') {
            $returnIds = collect($request->input('_return_ids', []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->take((int) config('acreditaciones.limits.bulk_max_ids', 500))
                ->values()
                ->all();

            return redirect()
                ->route(
                    'gestion-humana.acreditaciones.export-apo',
                    $returnIds === [] ? [] : ['ids' => $returnIds],
                )
                ->with('status', 'Acreditado actualizado correctamente. Se revalidaron los candidatos en Export Apo.');
        }

        return redirect()
            ->route('gestion-humana.acreditaciones.acreditados', $acreditadosQuery)
            ->with('status', $acreditadosStatus);
    }

    /**
     * @return array{
     *     document_number: string,
     *     document_numbers: list<string>,
     *     cargo: string,
     *     cargo_apo: string,
     *     estado: string,
     *     renovacion: string,
     *     vigencia_desde: string,
     *     vigencia_hasta: string,
     *     ficha_estado: string,
     * }
     */
    private function acreditadoFiltersFromRequest(Request $request): array
    {
        $fichaEstado = (string) $request->input('ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO);
        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
            'todos',
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        return [
            'document_number' => trim((string) $request->input('document_number', '')),
            'document_numbers' => app(DocumentNumberListParser::class)->fromInput(
                $request->input('document_numbers'),
            ),
            'cargo' => trim((string) $request->input('cargo', '')),
            'cargo_apo' => trim((string) $request->input('cargo_apo', '')),
            'estado' => (string) $request->input('estado', 'todos'),
            'renovacion' => (string) $request->input('renovacion', 'todos'),
            'vigencia_desde' => trim((string) $request->input('vigencia_desde', '')),
            'vigencia_hasta' => trim((string) $request->input('vigencia_hasta', '')),
            'ficha_estado' => $fichaEstado,
        ];
    }

    /**
     * @param  array{
     *     document_number: string,
     *     document_numbers: list<string>,
     *     cargo: string,
     *     cargo_apo: string,
     *     estado: string,
     *     renovacion: string,
     *     vigencia_desde: string,
     *     vigencia_hasta: string,
     *     ficha_estado: string,
     * }  $filters
     * @return array<string, string>
     */
    private function activeAcreditadoFilterQuery(array $filters): array
    {
        $query = [];

        if ($filters['document_number'] !== '') {
            $query['document_number'] = $filters['document_number'];
        }

        if ($filters['document_numbers'] !== []) {
            $query['document_numbers'] = app(DocumentNumberListParser::class)
                ->toQueryValue($filters['document_numbers']);
        }

        if ($filters['cargo'] !== '') {
            $query['cargo'] = $filters['cargo'];
        }

        if ($filters['cargo_apo'] !== '') {
            $query['cargo_apo'] = $filters['cargo_apo'];
        }

        if ($filters['estado'] !== '' && $filters['estado'] !== 'todos') {
            $query['estado'] = $filters['estado'];
        }

        if ($filters['renovacion'] !== '' && $filters['renovacion'] !== 'todos') {
            $query['renovacion'] = $filters['renovacion'];
        }

        if ($filters['vigencia_desde'] !== '') {
            $query['vigencia_desde'] = $filters['vigencia_desde'];
        }

        if ($filters['vigencia_hasta'] !== '') {
            $query['vigencia_hasta'] = $filters['vigencia_hasta'];
        }

        // Always pass ficha_estado so datatable/export keep the same default (activo).
        $query['ficha_estado'] = $filters['ficha_estado'];

        return $query;
    }

    /**
     * @return array{fecha_reporte: string, origen: string, q: string}
     */
    private function reporteDiarioFiltersFromRequest(Request $request): array
    {
        $origen = (string) $request->input('origen', 'todos');
        if ($origen !== 'todos' && ! in_array($origen, AcreditacionReporteDiarioFila::ORIGENES, true)) {
            $origen = 'todos';
        }

        return [
            'fecha_reporte' => $this->reporteDiarioListService->resolveFecha(
                $request->input('fecha_reporte'),
            ),
            'origen' => $origen,
            'q' => trim((string) $request->input('q', '')),
        ];
    }

    /**
     * @param  array{fecha_reporte: string, origen: string, q: string}  $filters
     * @return array<string, string>
     */
    private function activeReporteDiarioFilterQuery(array $filters): array
    {
        $query = [
            'fecha_reporte' => $filters['fecha_reporte'],
        ];

        if ($filters['origen'] !== '' && $filters['origen'] !== 'todos') {
            $query['origen'] = $filters['origen'];
        }

        if ($filters['q'] !== '') {
            $query['q'] = $filters['q'];
        }

        return $query;
    }

    /**
     * @return array{
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     cargo_apo: string,
     *     ficha_estado: string,
     *     anio: int,
     * }
     */
    private function dashboardFiltersFromRequest(Request $request): array
    {
        return [
            'fecha_desde' => trim((string) $request->query('fecha_desde', '')),
            'fecha_hasta' => trim((string) $request->query('fecha_hasta', '')),
            'cargo_apo' => trim((string) $request->query('cargo_apo', '')),
            'ficha_estado' => trim((string) $request->query('ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO)),
            'anio' => (int) $request->query('anio', now()->year),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function acreditadoPayloadFromValidated(array $validated, string $fullName, string $cargo): array
    {
        $vigencia = isset($validated['vigencia_acr']) && $validated['vigencia_acr'] !== null
            ? Carbon::parse($validated['vigencia_acr'])
            : null;
        $solicitud = isset($validated['fecha_solicitud']) && $validated['fecha_solicitud'] !== null
            ? Carbon::parse($validated['fecha_solicitud'])
            : null;

        return [
            'document_number' => (string) $validated['document_number'],
            'full_name' => $fullName,
            'cargo' => $cargo,
            'cargo_apo' => (string) $validated['cargo_apo'],
            'vigencia_acr' => $validated['vigencia_acr'] ?? null,
            'fecha_solicitud' => $validated['fecha_solicitud'] ?? null,
            'estado' => $this->estadoCalculator->calculate($solicitud, $vigencia),
            'renovacion' => $validated['renovacion'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
        ];
    }
}
