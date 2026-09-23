<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\AcreditacionesImportTemplateExport;
use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\Acreditaciones\ImportAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\StoreAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\StoreAcreditacionCargoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\UpdateAcreditacionAcreditadoRequest;
use App\Http\Requests\GestionHumana\Acreditaciones\UpdateAcreditacionCargoRequest;
use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\EmployeeFichaProfile;
use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionAcreditadoDatatableService;
use App\Services\GestionHumana\AcreditacionAcreditadoListService;
use App\Services\GestionHumana\AcreditacionCargoCatalogService;
use App\Services\GestionHumana\AcreditacionesAuditLogService;
use App\Services\GestionHumana\AcreditacionEstadoCalculator;
use App\Services\GestionHumana\AcreditacionImportService;
use App\Traits\HasAcreditacionesTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return redirect()->route('gestion-humana.acreditaciones.acreditados', $request->query());
    }

    public function dashboard(): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return view('areas.gestion_humana.acreditaciones.placeholder', [
            'subTabs' => $this->getAcreditacionesSubTabs('dashboard'),
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Gestion humana — indicadores de acreditaciones',
        ]);
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

        return view('areas.gestion_humana.acreditaciones.acreditados', [
            'subTabs' => $this->getAcreditacionesSubTabs('acreditados'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'filterEstadoOptions' => $filterEstadoOptions,
            'filterCargoApoOptions' => $filterCargoApoOptions,
            'cargoApoOptions' => $cargoApoOptions,
            'lookupUrl' => route('gestion-humana.acreditaciones.acreditados.lookup'),
            'datatableUrl' => route(
                'gestion-humana.acreditaciones.acreditados.datatable',
                $this->activeAcreditadoFilterQuery($filters),
            ),
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

    public function acreditadosLookup(Request $request): JsonResponse
    {
        abort_unless($this->acreditacionesAccess->canEdit(auth()->user()), 403);

        $cedula = trim((string) $request->query('cedula', ''));

        if ($cedula === '') {
            return response()->json(['found' => false]);
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->first(['id', 'document_number', 'full_name']);

        if ($profile === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'document_number' => $profile->document_number,
            'full_name' => $profile->full_name,
            'employee_ficha_profile_id' => $profile->id,
        ]);
    }

    public function storeAcreditado(StoreAcreditacionAcreditadoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->acreditadoPayloadFromValidated($validated, $request->resolvedFullName());

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

        return redirect()
            ->route('gestion-humana.acreditaciones.acreditados')
            ->with('status', 'Acreditado creado correctamente.');
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
            'observaciones',
        ]);

        $payload = $this->acreditadoPayloadFromValidated(
            $request->validated(),
            $request->resolvedFullName(),
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

        return redirect()
            ->route(
                'gestion-humana.acreditaciones.acreditados',
                $this->activeAcreditadoFilterQuery($this->acreditadoFiltersFromRequest($request)),
            )
            ->with('status', 'Acreditado actualizado correctamente.');
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
            ['key' => 'observaciones', 'label' => 'OBSERVACIONES'],
            ['key' => 'fecha_solicitud', 'label' => 'FECHA SOLICITUD'],
        ];

        $data = $rows->map(fn (AcreditacionAcreditado $row): array => [
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'cargo' => $row->cargo,
            'cargo_apo' => $row->cargo_apo,
            'vigencia_acr' => optional($row->vigencia_acr)?->format('Y-m-d'),
            'estado' => $this->estadoCalculator->estadoLabel((string) $row->estado),
            'observaciones' => $row->observaciones,
            'fecha_solicitud' => optional($row->fecha_solicitud)?->format('Y-m-d'),
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

    public function reporteDiario(): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return view('areas.gestion_humana.acreditaciones.placeholder', [
            'subTabs' => $this->getAcreditacionesSubTabs('reporte_diario'),
            'pageTitle' => 'Reporte Diario',
            'pageDescription' => 'Gestion humana — reporte diario de acreditaciones',
        ]);
    }

    public function validaciones(): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return view('areas.gestion_humana.acreditaciones.placeholder', [
            'subTabs' => $this->getAcreditacionesSubTabs('validaciones'),
            'pageTitle' => 'Validaciones',
            'pageDescription' => 'Gestion humana — validaciones de acreditaciones',
        ]);
    }

    public function exportApo(): View
    {
        abort_unless($this->acreditacionesAccess->canView(auth()->user()), 403);

        return view('areas.gestion_humana.acreditaciones.placeholder', [
            'subTabs' => $this->getAcreditacionesSubTabs('export_apo'),
            'pageTitle' => 'Export Apo',
            'pageDescription' => 'Gestion humana — exportación APO',
        ]);
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
        ]);
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
     * @return array{
     *     document_number: string,
     *     cargo: string,
     *     cargo_apo: string,
     *     estado: string,
     *     vigencia_desde: string,
     *     vigencia_hasta: string,
     * }
     */
    private function acreditadoFiltersFromRequest(Request $request): array
    {
        return [
            'document_number' => trim((string) $request->input('document_number', '')),
            'cargo' => trim((string) $request->input('cargo', '')),
            'cargo_apo' => trim((string) $request->input('cargo_apo', '')),
            'estado' => (string) $request->input('estado', 'todos'),
            'vigencia_desde' => trim((string) $request->input('vigencia_desde', '')),
            'vigencia_hasta' => trim((string) $request->input('vigencia_hasta', '')),
        ];
    }

    /**
     * @param  array{
     *     document_number: string,
     *     cargo: string,
     *     cargo_apo: string,
     *     estado: string,
     *     vigencia_desde: string,
     *     vigencia_hasta: string,
     * }  $filters
     * @return array<string, string>
     */
    private function activeAcreditadoFilterQuery(array $filters): array
    {
        $query = [];

        if ($filters['document_number'] !== '') {
            $query['document_number'] = $filters['document_number'];
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

        if ($filters['vigencia_desde'] !== '') {
            $query['vigencia_desde'] = $filters['vigencia_desde'];
        }

        if ($filters['vigencia_hasta'] !== '') {
            $query['vigencia_hasta'] = $filters['vigencia_hasta'];
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function acreditadoPayloadFromValidated(array $validated, string $fullName): array
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
            'cargo' => (string) $validated['cargo'],
            'cargo_apo' => (string) $validated['cargo_apo'],
            'vigencia_acr' => $validated['vigencia_acr'] ?? null,
            'fecha_solicitud' => $validated['fecha_solicitud'] ?? null,
            'estado' => $this->estadoCalculator->calculate($solicitud, $vigencia),
            'observaciones' => $validated['observaciones'] ?? null,
        ];
    }
}
