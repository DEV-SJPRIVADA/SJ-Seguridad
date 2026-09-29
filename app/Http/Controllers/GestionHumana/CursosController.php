<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\BaseExport;
use App\Exports\CursosImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\Cursos\BulkMarkSolicitadoEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\ImportEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\OmitEmployeeCursoPendingRequest;
use App\Http\Requests\GestionHumana\Cursos\StoreEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\UpdateEmployeeCursoRequest;
use App\Http\Requests\GestionHumana\Cursos\UploadEmployeeCursoDocumentRequest;
use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeCursoPending;
use App\Models\EmployeeFichaProfile;
use App\Services\Access\CursosAccessService;
use App\Services\GestionHumana\CursosAuditLogService;
use App\Services\GestionHumana\EmployeeCursoDashboardService;
use App\Services\GestionHumana\EmployeeCursoDatatableService;
use App\Services\GestionHumana\EmployeeCursoDocumentService;
use App\Services\GestionHumana\EmployeeCursoEstadoSyncService;
use App\Services\GestionHumana\EmployeeCursoImportService;
use App\Services\GestionHumana\EmployeeCursoListService;
use App\Services\GestionHumana\EmployeeCursoPendingService;
use App\Services\GestionHumana\EmployeeCursoValidacionesDatatableService;
use App\Services\GestionHumana\EmployeeCursoValidacionesService;
use App\Support\DocumentNumberListParser;
use App\Traits\HasCursosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        private readonly EmployeeCursoDatatableService $datatableService,
        private readonly EmployeeCursoDashboardService $dashboardService,
        private readonly EmployeeCursoDocumentService $documentService,
        private readonly EmployeeCursoEstadoSyncService $estadoSyncService,
        private readonly EmployeeCursoPendingService $pendingService,
        private readonly EmployeeCursoValidacionesService $validacionesService,
        private readonly EmployeeCursoValidacionesDatatableService $validacionesDatatableService,
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

        $canEdit = $this->cursosAccess->canEdit(auth()->user());
        $colaMode = (string) $request->query('cola', '') === 'nuevos-sin-curso';

        if ($colaMode && ! $canEdit) {
            abort(403);
        }

        $pendingCount = $canEdit ? $this->pendingService->countPendingActivos() : 0;
        $pendingRows = $colaMode
            ? $this->pendingService->listPendingActivos()
            : collect();

        $filters = $this->filtersFromRequest($request);

        $tipoOptions = CursoTipo::query()
            ->ordered()
            ->get(['id', 'tipo_curso'])
            ->map(fn (CursoTipo $tipo): array => [
                'value' => (string) $tipo->id,
                'label' => $tipo->tipo_curso,
            ])
            ->values()
            ->all();

        $escuelaOptions = CursoEscuela::query()
            ->active()
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('nombre')
            ->orderBy('id')
            ->get(['id', 'nombre'])
            ->map(fn (CursoEscuela $escuela): array => [
                'value' => (string) $escuela->id,
                'label' => $escuela->nombre,
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
            'canEdit' => $canEdit,
            'colaMode' => $colaMode,
            'pendingCount' => $pendingCount,
            'pendingRows' => $pendingRows,
            'filters' => $filters,
            'tipoOptions' => $tipoOptions,
            'escuelaOptions' => $escuelaOptions,
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
            'datatableUrl' => route('gestion-humana.cursos.registros.datatable', $this->activeFilterQuery($filters)),
            'bulkSelectableUrl' => route('gestion-humana.cursos.registros.bulk-selectable', $this->activeFilterQuery($filters)),
            'exportUrl' => route('gestion-humana.cursos.registros.export', $this->activeFilterQuery($filters)),
            'importTemplateUrl' => route('gestion-humana.cursos.registros.import-template'),
            'importUrl' => route('gestion-humana.cursos.registros.import'),
            'bulkMarkSolicitadoUrl' => route('gestion-humana.cursos.registros.bulk-mark-solicitado'),
            'activeFilterQuery' => $this->activeFilterQuery($filters),
            'colaQueueUrl' => route('gestion-humana.cursos.registros', ['cola' => 'nuevos-sin-curso']),
            'colaExitUrl' => route('gestion-humana.cursos.registros'),
        ]);
    }

    public function validaciones(Request $request): View
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $canEdit = $this->cursosAccess->canEdit(auth()->user());
        $counts = $this->validacionesService->counts();
        $colaLabels = $this->validacionesService->colaLabels();
        $defaultCola = EmployeeCursoValidacionesService::COLA_SIN_CURSO;

        $tipoOptions = CursoTipo::query()
            ->ordered()
            ->get(['id', 'tipo_curso'])
            ->map(fn (CursoTipo $tipo): array => [
                'value' => (string) $tipo->id,
                'label' => $tipo->tipo_curso,
            ])
            ->values()
            ->all();

        $escuelaOptions = CursoEscuela::query()
            ->active()
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('nombre')
            ->orderBy('id')
            ->get(['id', 'nombre'])
            ->map(fn (CursoEscuela $escuela): array => [
                'value' => (string) $escuela->id,
                'label' => $escuela->nombre,
            ])
            ->values()
            ->all();

        $estadoOptions = [
            ['value' => EmployeeCurso::ESTADO_SOLICITADO, 'label' => 'SOLICITADO'],
            ['value' => EmployeeCurso::ESTADO_ACTUALIZADO, 'label' => 'ACTUALIZADO'],
            ['value' => EmployeeCurso::ESTADO_PENDIENTE, 'label' => 'PENDIENTE'],
        ];

        $colaDefs = [
            EmployeeCursoValidacionesService::COLA_SIN_CURSO => [
                'label' => $colaLabels[EmployeeCursoValidacionesService::COLA_SIN_CURSO] ?? 'Activos sin curso',
                'columns' => array_values(array_filter([
                    ['data' => 0, 'title' => 'Cédula'],
                    ['data' => 1, 'title' => 'Nombre'],
                    ['data' => 2, 'title' => 'Cargo'],
                    $canEdit ? ['data' => 3, 'title' => 'Acciones', 'orderable' => false, 'searchable' => false] : null,
                ])),
            ],
            EmployeeCursoValidacionesService::COLA_POR_ACTUALIZAR_VENCIDOS => [
                'label' => $colaLabels[EmployeeCursoValidacionesService::COLA_POR_ACTUALIZAR_VENCIDOS] ?? 'Por actualizar / vencidos',
                'columns' => array_values(array_filter([
                    $canEdit ? ['data' => 0, 'title' => '', 'orderable' => false, 'searchable' => false] : null,
                    ['data' => $canEdit ? 1 : 0, 'title' => 'Cédula'],
                    ['data' => $canEdit ? 2 : 1, 'title' => 'Nombre'],
                    ['data' => $canEdit ? 3 : 2, 'title' => 'Tipo curso'],
                    ['data' => $canEdit ? 4 : 3, 'title' => 'No.CURSO'],
                    ['data' => $canEdit ? 5 : 4, 'title' => 'Fecha exp.'],
                    ['data' => $canEdit ? 6 : 5, 'title' => 'Vigencia'],
                    ['data' => $canEdit ? 7 : 6, 'title' => 'Estado'],
                    $canEdit ? ['data' => 8, 'title' => 'Acciones', 'orderable' => false, 'searchable' => false] : null,
                ])),
            ],
        ];

        $exportUrls = [];
        foreach (EmployeeCursoValidacionesService::COLAS as $colaCode) {
            $exportUrls[$colaCode] = route('gestion-humana.cursos.validaciones.export', ['cola' => $colaCode]);
        }

        $sessionErrors = $request->session()->get('errors');
        $showNuevoModal = $canEdit
            && $sessionErrors
            && method_exists($sessionErrors, 'any')
            && $sessionErrors->any()
            && (string) old('_return_context') === 'validaciones';

        return view('areas.gestion_humana.cursos.validaciones', [
            'subTabs' => $this->getCursosSubTabs('validaciones'),
            'canEdit' => $canEdit,
            'counts' => $counts,
            'colaLabels' => $colaLabels,
            'colaDefs' => $colaDefs,
            'defaultCola' => $defaultCola,
            'tipoOptions' => $tipoOptions,
            'escuelaOptions' => $escuelaOptions,
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
                ['value' => '', 'label' => 'Todas (ACTUALIZAR + VENCIDO)'],
                ['value' => EmployeeCurso::VIGENCIA_ACTUALIZAR, 'label' => 'ACTUALIZAR'],
                ['value' => EmployeeCurso::VIGENCIA_VENCIDO, 'label' => 'VENCIDO'],
            ],
            'lookupUrl' => route('gestion-humana.cursos.registros.lookup'),
            'datatableUrl' => route('gestion-humana.cursos.validaciones.datatable'),
            'bulkSelectableUrl' => route('gestion-humana.cursos.validaciones.bulk-selectable'),
            'bulkMarkSolicitadoUrl' => route('gestion-humana.cursos.registros.bulk-mark-solicitado'),
            'exportUrls' => $exportUrls,
            'showNuevoModal' => $showNuevoModal,
        ]);
    }

    public function validacionesDatatable(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $cola = (string) $request->query('cola', EmployeeCursoValidacionesService::COLA_SIN_CURSO);
        if (! $this->validacionesService->isValidCola($cola)) {
            return response()->json(['error' => 'Cola no válida.'], 422);
        }

        return $this->validacionesDatatableService->respond(
            $request,
            $cola,
            $this->validacionesFiltersFromRequest($request),
            $this->cursosAccess->canEdit(auth()->user()),
        );
    }

    public function validacionesExport(Request $request): StreamedResponse|RedirectResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $cola = (string) $request->query('cola', '');
        if (! $this->validacionesService->isValidCola($cola)) {
            return redirect()
                ->route('gestion-humana.cursos.validaciones')
                ->with('error', 'Cola de validación no válida.');
        }

        $filters = $this->validacionesFiltersFromRequest($request);
        $columns = $this->validacionesService->exportColumns($cola);
        $label = $this->validacionesService->colaLabels()[$cola] ?? $cola;

        if ($cola === EmployeeCursoValidacionesService::COLA_SIN_CURSO) {
            $data = $this->validacionesService->sinCursoQuery($filters)
                ->get(['document_number', 'full_name', 'position_name'])
                ->map(fn (EmployeeFichaProfile $profile): array => [
                    'document_number' => (string) $profile->document_number,
                    'full_name' => (string) ($profile->full_name ?: ''),
                    'position_name' => (string) ($profile->position_name ?: ''),
                ]);
        } else {
            $data = $this->validacionesService->porActualizarVencidosQuery($filters)
                ->with(['cursoTipo:id,tipo_curso'])
                ->get()
                ->map(fn (EmployeeCurso $curso): array => [
                    'document_number' => (string) $curso->document_number,
                    'full_name' => (string) $curso->full_name,
                    'tipo_curso' => (string) ($curso->cursoTipo?->tipo_curso ?: ''),
                    'numero_curso' => (string) $curso->numero_curso,
                    'fecha_expedicion' => optional($curso->fecha_expedicion)?->format('Y-m-d') ?: '',
                    'vigencia' => $curso->computeVigencia(),
                    'estado' => (string) ($curso->estado ?: ''),
                ]);
        }

        return (new BaseExport(
            $data,
            $columns,
            sprintf('cursos_validaciones_%s.xlsx', $cola),
            sprintf('Cursos validaciones — %s', $label),
        ))->download();
    }

    public function validacionesBulkSelectable(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        $filters = $this->validacionesFiltersFromRequest($request);
        $rows = $this->validacionesService->porActualizarVencidosQuery($filters, ordered: false)
            ->with(['cursoTipo:id,tipo_curso'])
            ->where('estado', '!=', EmployeeCurso::ESTADO_SOLICITADO)
            ->orderByDesc('fecha_expedicion')
            ->orderByDesc('id')
            ->get([
                'id',
                'document_number',
                'full_name',
                'curso_tipo_id',
                'numero_curso',
                'estado',
                'fecha_expedicion',
            ])
            ->map(fn (EmployeeCurso $curso): array => [
                'id' => $curso->id,
                'document_number' => $curso->document_number,
                'full_name' => $curso->full_name,
                'tipo_curso' => $curso->cursoTipo?->tipo_curso ?? '—',
                'numero_curso' => $curso->numero_curso,
                'estado' => $curso->estado ?: '—',
                'vigencia' => $curso->computeVigencia(),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['count' => count($rows)],
        ]);
    }

    public function omitPending(
        OmitEmployeeCursoPendingRequest $request,
        EmployeeCursoPending $pending,
    ): RedirectResponse {
        $this->pendingService->omit(
            $pending,
            $request->validated('omit_reason'),
            $request->user()?->id,
        );

        return redirect()
            ->route('gestion-humana.cursos.registros', ['cola' => 'nuevos-sin-curso'])
            ->with('status', 'Persona omitida de la cola «Nuevos sin curso».');
    }

    public function bulkMarkSolicitado(BulkMarkSolicitadoEmployeeCursoRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = array_values(array_map('intval', $request->validated('ids')));

        $updatedCount = 0;
        $skippedAlreadySolicitado = 0;

        DB::transaction(function () use ($ids, &$updatedCount, &$skippedAlreadySolicitado): void {
            $cursos = EmployeeCurso::query()
                ->whereIn('id', $ids)
                ->get(['id', 'document_number', 'numero_curso', 'estado']);

            $alreadySolicitado = $cursos->where('estado', EmployeeCurso::ESTADO_SOLICITADO);
            $toUpdate = $cursos->reject(
                fn (EmployeeCurso $curso): bool => $curso->estado === EmployeeCurso::ESTADO_SOLICITADO
            );

            $skippedAlreadySolicitado = $alreadySolicitado->count();

            if ($toUpdate->isEmpty()) {
                return;
            }

            $updateIds = $toUpdate->pluck('id')->all();

            $updatedCount = EmployeeCurso::query()
                ->whereIn('id', $updateIds)
                ->where('estado', '!=', EmployeeCurso::ESTADO_SOLICITADO)
                ->update([
                    'estado' => EmployeeCurso::ESTADO_SOLICITADO,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            $this->auditLogService->logEvent(
                eventType: 'employee_curso',
                action: 'bulk_mark_solicitado',
                reason: 'Marcado masivo a SOLICITADO (irreversible desde esta acción).',
                metadata: [
                    'requested_ids' => $ids,
                    'updated_ids' => $updateIds,
                    'updated_count' => $updatedCount,
                    'skipped_already_solicitado' => $skippedAlreadySolicitado,
                ],
                userId: (int) auth()->id(),
            );
        });

        $filters = $this->filtersFromRequest($request);

        if ($updatedCount === 0) {
            return $this->redirectAfterCursoMutation($request, $this->activeFilterQuery($filters))
                ->with('error', 'No se actualizó ningún registro. Los seleccionados ya estaban en SOLICITADO o no eran válidos.');
        }

        $message = $updatedCount === 1
            ? '1 registro marcado como SOLICITADO.'
            : "{$updatedCount} registros marcados como SOLICITADO.";

        if ($skippedAlreadySolicitado > 0) {
            $message .= " Se omitieron {$skippedAlreadySolicitado} que ya estaban solicitados.";
        }

        return $this->redirectAfterCursoMutation($request, $this->activeFilterQuery($filters))
            ->with('status', $message);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        return $this->datatableService->respond(
            $request,
            $this->filtersFromRequest($request),
            $this->cursosAccess->canEdit(auth()->user()),
        );
    }

    public function bulkSelectable(Request $request): JsonResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        $rows = $this->datatableService->bulkSelectableRows($this->filtersFromRequest($request));

        return response()->json([
            'data' => $rows,
            'meta' => [
                'count' => count($rows),
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
        if (! $request->wantsCourseRecord()) {
            $cedula = (string) $request->validated('document_number');

            EmployeeFichaProfile::query()
                ->where('document_number', $cedula)
                ->update(['requires_courses' => false]);

            $pending = EmployeeCursoPending::query()
                ->forDocumentNumber($cedula)
                ->pending()
                ->first();

            if ($pending !== null) {
                $this->pendingService->omit(
                    $pending,
                    'No requiere cursos',
                    auth()->id(),
                );
            }

            $this->auditLogService->logEvent(
                eventType: 'employee_curso',
                action: 'disable_requires_courses',
                metadata: [
                    'document_number' => $cedula,
                    'requires_courses' => false,
                    'pending_omitted' => $pending !== null,
                ],
                userId: (int) auth()->id(),
            );

            return $this->redirectAfterCursoMutation($request)
                ->with('status', 'Ficha actualizada: la persona no requiere cursos.');
        }

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

        $this->pendingService->resolveByDocument(
            (string) $curso->document_number,
            $curso,
            auth()->id(),
        );

        EmployeeFichaProfile::query()
            ->where('document_number', $curso->document_number)
            ->update(['requires_courses' => true]);

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

        return $this->redirectAfterCursoMutation($request)
            ->with('status', 'Registro de curso creado correctamente.');
    }

    public function update(UpdateEmployeeCursoRequest $request, EmployeeCurso $employeeCurso): RedirectResponse
    {
        $before = $employeeCurso->only([
            'document_number',
            'full_name',
            'curso_tipo_id',
            'curso_escuela_id',
            'escuela_codigo',
            'escuela_nit',
            'escuela_nombre',
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

        return $this->redirectAfterCursoMutation($request, $request->query())
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
            ['key' => 'escuela_nombre', 'label' => 'ESCUELA'],
            ['key' => 'escuela_codigo', 'label' => 'CODIGO ESCUELA'],
            ['key' => 'escuela_nit', 'label' => 'NIT ESCUELA'],
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
            'escuela_nombre' => $curso->escuela_nombre,
            'escuela_codigo' => $curso->escuela_codigo,
            'escuela_nit' => $curso->escuela_nit,
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
     *     document_numbers: list<string>,
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
            'document_number' => trim((string) $request->input('document_number', '')),
            'document_numbers' => app(DocumentNumberListParser::class)->fromInput(
                $request->input('document_numbers'),
            ),
            'full_name' => trim((string) $request->input('full_name', '')),
            'curso_tipo_id' => (string) $request->input('curso_tipo_id', ''),
            'vigencia' => strtoupper(trim((string) $request->input('vigencia', ''))),
            'estado' => (string) $request->input('estado', 'todos'),
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
     *     document_numbers: list<string>,
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

        if ($filters['document_numbers'] !== []) {
            $query['document_numbers'] = app(DocumentNumberListParser::class)
                ->toQueryValue($filters['document_numbers']);
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
     * @return array{
     *     document_number: string,
     *     document_numbers: list<string>,
     *     full_name: string,
     *     curso_tipo_id: string,
     *     vigencia: string,
     *     estado: string,
     * }
     */
    private function validacionesFiltersFromRequest(Request $request): array
    {
        $documentNumbers = app(DocumentNumberListParser::class)->fromInput(
            $request->input('document_numbers', $request->query('document_numbers'))
        );

        return [
            'document_number' => trim((string) $request->input('document_number', '')),
            'document_numbers' => $documentNumbers,
            'full_name' => trim((string) $request->input('full_name', '')),
            'curso_tipo_id' => (string) $request->input('curso_tipo_id', ''),
            'vigencia' => strtoupper(trim((string) $request->input('vigencia', ''))),
            'estado' => (string) $request->input('estado', 'todos'),
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function redirectAfterCursoMutation(Request $request, array $query = []): RedirectResponse
    {
        $returnContext = (string) $request->input('_return_context', $request->query('_return_context', ''));

        if ($returnContext === 'validaciones') {
            return redirect()->route('gestion-humana.cursos.validaciones', array_filter([
                'cola' => (string) $request->input('cola', $request->query('cola', '')),
            ]));
        }

        return redirect()->route('gestion-humana.cursos.registros', $query);
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

        $escuela = CursoEscuela::query()->findOrFail((int) $validated['curso_escuela_id']);

        return [
            'document_number' => $cedula,
            'full_name' => (string) $validated['full_name'],
            'curso_tipo_id' => (int) $validated['curso_tipo_id'],
            'curso_escuela_id' => $escuela->id,
            'escuela_codigo' => $escuela->codigo,
            'escuela_nit' => $escuela->nit,
            'escuela_nombre' => $escuela->nombre,
            'fecha_expedicion' => $validated['fecha_expedicion'],
            'numero_curso' => (string) $validated['numero_curso'],
            'estado' => $validated['estado'] ?? EmployeeCurso::ESTADO_ACTUALIZADO,
            'observaciones' => $validated['observaciones'] ?? null,
            'employee_ficha_profile_id' => $profileId,
        ];
    }
}
