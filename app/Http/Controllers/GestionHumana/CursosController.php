<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\BaseExport;
use App\Exports\CursosImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\Cursos\ImportEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\StoreEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\UpdateEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\UploadEmployeeCursoDocumentRequest;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Services\Access\CursosAccessService;
use App\Services\GestionHumana\CursosAuditLogService;
use App\Services\GestionHumana\EmployeeCursoDashboardService;
use App\Services\GestionHumana\EmployeeCursoDocumentService;
use App\Services\GestionHumana\EmployeeCursoEstadoSyncService;
use App\Services\GestionHumana\EmployeeCursoImportService;
use App\Services\GestionHumana\EmployeeCursoListService;
use App\Traits\HasCursosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CursosController extends Controller
{
    use HasCursosTabs;

    public function __construct(
        private readonly CursosAccessService $cursosAccess,
        private readonly CursosAuditLogService $auditLogService,
        private readonly EmployeeCursoListService $listService,
        private readonly EmployeeCursoDashboardService $dashboardService,
        private readonly EmployeeCursoDocumentService $documentService,
        private readonly EmployeeCursoEstadoSyncService $estadoSyncService,
        private readonly CursosImportTemplateExport $importTemplateExport,
        private readonly EmployeeCursoImportService $importService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        return redirect()->route('gestion-humana.cursos.dashboard', $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $payload = $this->dashboardService->metrics($filters);

        $tipoOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $this->dashboardService->tipoOptions(),
        );

        return view('areas.gestion_humana.cursos.dashboard', [
            'subTabs' => $this->getCursosSubTabs('dashboard'),
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.cursos.dashboard.metrics'),
            'tipoOptions' => $tipoOptions,
            'yearOptions' => $this->dashboardService->yearOptions(),
            'filterVigenciaOptions' => [
                ['value' => '', 'label' => 'Todas'],
                ['value' => EmployeeCurso::VIGENCIA_VIGENTE, 'label' => 'VIGENTE'],
                ['value' => EmployeeCurso::VIGENCIA_ACTUALIZAR, 'label' => 'ACTUALIZAR'],
                ['value' => EmployeeCurso::VIGENCIA_VENCIDO, 'label' => 'VENCIDO'],
            ],
            'filterEstadoOptions' => [
                ['value' => 'todos', 'label' => 'Todos'],
                ['value' => EmployeeCurso::ESTADO_SOLICITADO, 'label' => 'SOLICITADO'],
                ['value' => EmployeeCurso::ESTADO_ACTUALIZADO, 'label' => 'ACTUALIZADO'],
                ['value' => EmployeeCurso::ESTADO_PENDIENTE, 'label' => 'PENDIENTE'],
            ],
        ]);
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        return response()->json(
            $this->dashboardService->metrics($this->dashboardFiltersFromRequest($request))
        );
    }

    public function registros(Request $request): View
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $filters = $this->filtersFromRequest($request);
        $registros = $this->listService->all($filters);

        $tipoOptions = CursoTipo::query()
            ->ordered()
            ->get(['id', 'tipo_curso'])
            ->map(fn (CursoTipo $tipo): array => [
                'value' => (string) $tipo->id,
                'label' => $tipo->tipo_curso,
            ])
            ->values()
            ->all();

        $estadoOptions = [
            ['value' => EmployeeCurso::ESTADO_SOLICITADO, 'label' => 'SOLICITADO'],
            ['value' => EmployeeCurso::ESTADO_ACTUALIZADO, 'label' => 'ACTUALIZADO'],
            ['value' => EmployeeCurso::ESTADO_PENDIENTE, 'label' => 'PENDIENTE'],
        ];

        return view('areas.gestion_humana.cursos.registros', [
            'subTabs' => $this->getCursosSubTabs('registros'),
            'canEdit' => $this->cursosAccess->canEdit(auth()->user()),
            'filters' => $filters,
            'registros' => $registros,
            'tipoOptions' => $tipoOptions,
            'estadoOptions' => $estadoOptions,
            'filterTipoOptions' => array_merge(
                [['value' => '', 'label' => 'Todos']],
                $tipoOptions,
            ),
            'filterEstadoOptions' => [
                ['value' => 'todos', 'label' => 'Todos'],
                ['value' => EmployeeCurso::ESTADO_SOLICITADO, 'label' => 'SOLICITADO'],
                ['value' => EmployeeCurso::ESTADO_ACTUALIZADO, 'label' => 'ACTUALIZADO'],
                ['value' => EmployeeCurso::ESTADO_PENDIENTE, 'label' => 'PENDIENTE'],
            ],
            'filterVigenciaOptions' => [
                ['value' => '', 'label' => 'Todas'],
                ['value' => EmployeeCurso::VIGENCIA_VIGENTE, 'label' => 'VIGENTE'],
                ['value' => EmployeeCurso::VIGENCIA_ACTUALIZAR, 'label' => 'ACTUALIZAR'],
                ['value' => EmployeeCurso::VIGENCIA_VENCIDO, 'label' => 'VENCIDO'],
            ],
            'lookupUrl' => route('gestion-humana.cursos.registros.lookup'),
            'exportUrl' => route('gestion-humana.cursos.registros.export', $this->activeFilterQuery($filters)),
            'importTemplateUrl' => route('gestion-humana.cursos.registros.import-template'),
            'importUrl' => route('gestion-humana.cursos.registros.import'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $filters = $this->filtersFromRequest($request);
        $rows = $this->listService->paginate($filters, (int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $rows->getCollection()->map(fn (EmployeeCurso $curso): array => $this->cursoToArray($curso))->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

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

    public function store(StoreEmployeeCursoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->payloadFromValidated($validated);

        $curso = new EmployeeCurso($payload);
        if (($payload['estado'] ?? null) !== EmployeeCurso::ESTADO_SOLICITADO) {
            $this->estadoSyncService->applyToModel($curso, preserveSolicitado: false);
            $payload['estado'] = $curso->estado;
        }

        $curso = EmployeeCurso::query()->create([
            ...$payload,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($request->hasFile('document')) {
            $this->documentService->storeOrReplace($curso, $request->file('document'));
        }

        $this->auditLogService->logEvent(
            eventType: 'employee_curso',
            action: 'store',
            metadata: [
                'employee_curso_id' => $curso->id,
                'document_number' => $curso->document_number,
                'numero_curso' => $curso->numero_curso,
                'has_document' => $curso->fresh()->hasDocument(),
            ],
            model: $curso,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.registros')
            ->with('status', 'Registro de curso creado correctamente.');
    }

    public function update(UpdateEmployeeCursoRequest $request, EmployeeCurso $employeeCurso): RedirectResponse
    {
        $before = $employeeCurso->only([
            'document_number',
            'full_name',
            'curso_tipo_id',
            'fecha_expedicion',
            'numero_curso',
            'estado',
            'observaciones',
            'employee_ficha_profile_id',
        ]);

        $payload = $this->payloadFromValidated($request->validated());

        $employeeCurso->fill($payload);
        if (($payload['estado'] ?? null) !== EmployeeCurso::ESTADO_SOLICITADO) {
            $this->estadoSyncService->applyToModel($employeeCurso, preserveSolicitado: false);
            $payload['estado'] = $employeeCurso->estado;
        }

        $employeeCurso->update([
            ...$payload,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'employee_curso',
            action: 'update',
            model: $employeeCurso,
            before: $before,
            after: $employeeCurso->only(array_keys($before)),
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.registros', $request->query())
            ->with('status', 'Registro de curso actualizado correctamente.');
    }

    public function destroy(EmployeeCurso $employeeCurso): RedirectResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        $metadata = [
            'employee_curso_id' => $employeeCurso->id,
            'document_number' => $employeeCurso->document_number,
            'numero_curso' => $employeeCurso->numero_curso,
        ];

        $this->documentService->deleteFile($employeeCurso);
        $employeeCurso->delete();

        $this->auditLogService->logEvent(
            eventType: 'employee_curso',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.registros')
            ->with('status', 'Registro de curso eliminado.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $filters = $this->filtersFromRequest($request);
        $rows = $this->listService->all($filters);

        $columns = [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'full_name', 'label' => 'NOMBRE COMPLETO'],
            ['key' => 'tipo_curso', 'label' => 'TIPO CURSO'],
            ['key' => 'fecha_expedicion', 'label' => 'FECHA EXPEDICION'],
            ['key' => 'numero_curso', 'label' => 'No.CURSO'],
            ['key' => 'vigencia', 'label' => 'VIGENCIA'],
            ['key' => 'estado', 'label' => 'ESTADO'],
            ['key' => 'observaciones', 'label' => 'OBSERVACIONES'],
            ['key' => 'documento', 'label' => 'DOCUMENTO'],
        ];

        $data = $rows->map(fn (EmployeeCurso $curso): array => [
            'document_number' => $curso->document_number,
            'full_name' => $curso->full_name,
            'tipo_curso' => $curso->cursoTipo?->tipo_curso,
            'fecha_expedicion' => optional($curso->fecha_expedicion)?->format('Y-m-d'),
            'numero_curso' => $curso->numero_curso,
            'vigencia' => $curso->computeVigencia(),
            'estado' => $curso->estado,
            'observaciones' => $curso->observaciones,
            'documento' => $curso->hasDocument() ? ($curso->document_original_name ?: 'Si') : '',
        ]);

        return (new BaseExport(
            $data,
            $columns,
            'cursos_'.now()->format('Y-m-d').'.xlsx',
            'Cursos — '.config('app.name'),
        ))->download();
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function import(ImportEmployeeCursoRequest $request): RedirectResponse
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
            Cache::put('cursos_import_report_'.$token, $stats['failures'], now()->addHour());
        }

        $this->auditLogService->logEvent(
            eventType: 'import',
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
            ->route('gestion-humana.cursos.registros')
            ->with('status', $message)
            ->with('import_failures', array_slice($stats['failures'], 0, 50))
            ->with('import_report_token', $token);
    }

    public function downloadImportReport(string $token): StreamedResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        /** @var list<array<string, mixed>>|null $failures */
        $failures = Cache::get('cursos_import_report_'.$token);

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
            'reporte_importacion_cursos_'.now()->format('Y-m-d_His').'.xlsx',
            'Errores importacion Cursos',
        ))->download();
    }

    public function downloadDocument(EmployeeCurso $employeeCurso): StreamedResponse|Response
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);
        abort_unless($employeeCurso->hasDocument(), 404);

        $disk = Storage::disk($this->documentService->disk());
        $path = (string) $employeeCurso->document_path;
        abort_unless($disk->exists($path), 404);

        return $disk->download(
            $path,
            $employeeCurso->document_original_name ?: basename($path),
        );
    }

    public function uploadDocument(UploadEmployeeCursoDocumentRequest $request, EmployeeCurso $employeeCurso): RedirectResponse
    {
        $this->documentService->storeOrReplace($employeeCurso, $request->file('document'));

        $this->auditLogService->logEvent(
            eventType: 'employee_curso',
            action: 'document_upload',
            metadata: [
                'employee_curso_id' => $employeeCurso->id,
                'document_original_name' => $employeeCurso->document_original_name,
            ],
            model: $employeeCurso,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.registros')
            ->with('status', 'Documento del curso guardado.');
    }

    public function destroyDocument(EmployeeCurso $employeeCurso): RedirectResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        $this->documentService->clear($employeeCurso);

        $this->auditLogService->logEvent(
            eventType: 'employee_curso',
            action: 'document_delete',
            metadata: [
                'employee_curso_id' => $employeeCurso->id,
            ],
            model: $employeeCurso,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.registros')
            ->with('status', 'Documento del curso eliminado.');
    }

    /**
     * @return array{
     *     document_number: string,
     *     full_name: string,
     *     curso_tipo_id: string,
     *     vigencia: string,
     *     estado: string,
     *     solo_actualizar: bool,
     * }
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'document_number' => trim((string) $request->query('document_number', '')),
            'full_name' => trim((string) $request->query('full_name', '')),
            'curso_tipo_id' => (string) $request->query('curso_tipo_id', ''),
            'vigencia' => strtoupper(trim((string) $request->query('vigencia', ''))),
            'estado' => (string) $request->query('estado', 'todos'),
            'solo_actualizar' => $request->boolean('solo_actualizar'),
        ];
    }

    /**
     * @return array{
     *     curso_tipo_id: string,
     *     vigencia: string,
     *     estado: string,
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     anio: int,
     * }
     */
    private function dashboardFiltersFromRequest(Request $request): array
    {
        return [
            'curso_tipo_id' => (string) $request->query('curso_tipo_id', ''),
            'vigencia' => strtoupper(trim((string) $request->query('vigencia', ''))),
            'estado' => (string) $request->query('estado', 'todos'),
            'fecha_desde' => trim((string) $request->query('fecha_desde', '')),
            'fecha_hasta' => trim((string) $request->query('fecha_hasta', '')),
            'anio' => (int) $request->query('anio', now()->year),
        ];
    }

    /**
     * @param  array{
     *     document_number: string,
     *     full_name: string,
     *     curso_tipo_id: string,
     *     vigencia: string,
     *     estado: string,
     *     solo_actualizar: bool,
     * }  $filters
     * @return array<string, int|string>
     */
    private function activeFilterQuery(array $filters): array
    {
        $query = [];

        if ($filters['document_number'] !== '') {
            $query['document_number'] = $filters['document_number'];
        }

        if ($filters['full_name'] !== '') {
            $query['full_name'] = $filters['full_name'];
        }

        if ($filters['curso_tipo_id'] !== '') {
            $query['curso_tipo_id'] = $filters['curso_tipo_id'];
        }

        if ($filters['vigencia'] !== '') {
            $query['vigencia'] = $filters['vigencia'];
        }

        if ($filters['estado'] !== '' && $filters['estado'] !== 'todos') {
            $query['estado'] = $filters['estado'];
        }

        if ($filters['solo_actualizar']) {
            $query['solo_actualizar'] = 1;
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payloadFromValidated(array $validated): array
    {
        $cedula = (string) $validated['document_number'];
        $profileId = EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->value('id');

        return [
            'document_number' => $cedula,
            'full_name' => (string) $validated['full_name'],
            'curso_tipo_id' => (int) $validated['curso_tipo_id'],
            'fecha_expedicion' => $validated['fecha_expedicion'],
            'numero_curso' => (string) $validated['numero_curso'],
            'estado' => $validated['estado'] ?? EmployeeCurso::ESTADO_ACTUALIZADO,
            'observaciones' => $validated['observaciones'] ?? null,
            'employee_ficha_profile_id' => $profileId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cursoToArray(EmployeeCurso $curso): array
    {
        $vigencia = $curso->computeVigencia();

        return [
            'id' => $curso->id,
            'document_number' => $curso->document_number,
            'full_name' => $curso->full_name,
            'curso_tipo_id' => $curso->curso_tipo_id,
            'tipo_curso' => $curso->cursoTipo?->tipo_curso,
            'fecha_expedicion' => optional($curso->fecha_expedicion)?->format('Y-m-d'),
            'numero_curso' => $curso->numero_curso,
            'vigencia' => $vigencia,
            'estado' => $curso->estado,
            'observaciones' => $curso->observaciones,
            'has_document' => $curso->hasDocument(),
            'document_original_name' => $curso->document_original_name,
        ];
    }
}
