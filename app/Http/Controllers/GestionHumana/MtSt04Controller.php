<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\MtSt04Export;
use App\Exports\MtSt04ImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\MtSt04\ImportMtSt04Request;
use App\Http\Requests\GestionHumana\MtSt04\StoreMtSt04RegistroRequest;
use App\Http\Requests\GestionHumana\MtSt04\UpdateMtSt04RegistroRequest;
use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Services\Access\MtSt04AccessService;
use App\Services\GestionHumana\MtSt04AuditLogService;
use App\Services\GestionHumana\MtSt04DashboardService;
use App\Services\GestionHumana\MtSt04DatatableService;
use App\Services\GestionHumana\MtSt04EstadoCalculator;
use App\Services\GestionHumana\MtSt04ImportService;
use App\Services\GestionHumana\MtSt04ListService;
use App\Traits\HasMtSt04Tabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MtSt04Controller extends Controller
{
    use HasMtSt04Tabs;

    public function __construct(
        private readonly MtSt04AccessService $mtSt04Access,
        private readonly MtSt04DatatableService $datatableService,
        private readonly MtSt04ListService $listService,
        private readonly MtSt04EstadoCalculator $estadoCalculator,
        private readonly MtSt04DashboardService $dashboardService,
        private readonly MtSt04ImportService $importService,
        private readonly MtSt04ImportTemplateExport $importTemplateExport,
        private readonly MtSt04AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->mtSt04Access->canView(auth()->user()), 403);

        return redirect()->route('gestion-humana.mt-st-04.dashboard', $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->mtSt04Access->canView(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $payload = $this->dashboardService->metrics($filters);

        $filterFichaEstadoOptions = [
            ['value' => EmployeeFichaProfile::STATUS_ACTIVO, 'label' => 'Activos en ficha'],
            ['value' => EmployeeFichaProfile::STATUS_DESVINCULADO, 'label' => 'Desvinculados'],
            ['value' => 'todos', 'label' => 'Todos (ficha)'],
        ];

        return view('areas.gestion_humana.mt_st_04.dashboard', [
            'subTabs' => $this->getMtSt04SubTabs('dashboard'),
            'canEdit' => $this->mtSt04Access->canEdit(auth()->user()),
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.mt-st-04.dashboard.metrics'),
            'filterFichaEstadoOptions' => $filterFichaEstadoOptions,
        ]);
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        abort_unless($this->mtSt04Access->canView(auth()->user()), 403);

        return response()->json(
            $this->dashboardService->metrics($this->dashboardFiltersFromRequest($request))
        );
    }

    public function matriz(Request $request): View
    {
        abort_unless($this->mtSt04Access->canView(auth()->user()), 403);

        $canEdit = $this->mtSt04Access->canEdit(auth()->user());
        $filters = $this->matrizFiltersFromRequest($request);

        $siNoOptions = collect(config('mt_st_04.si_no', []))
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        $estado1Options = [
            ['value' => 'todos', 'label' => 'Todos'],
            ['value' => MtSt04Registro::ESTADO_VIGENTE, 'label' => 'VIGENTE'],
            ['value' => MtSt04Registro::ESTADO_VENCERA, 'label' => 'VENCERA'],
            ['value' => MtSt04Registro::ESTADO_VENCIDO, 'label' => 'VENCIDO'],
            ['value' => '__empty__', 'label' => 'Sin estado'],
        ];

        $estado2Options = [
            ['value' => 'todos', 'label' => 'Todos'],
            ['value' => MtSt04Registro::ESTADO_VIGENTE, 'label' => 'VIGENTE'],
            ['value' => MtSt04Registro::ESTADO_VENCERA, 'label' => 'VENCERA'],
            ['value' => MtSt04Registro::ESTADO_VENCIDO, 'label' => 'VENCIDO'],
            ['value' => MtSt04Registro::ESTADO_NO_APLICA, 'label' => 'NO APLICA'],
            ['value' => '__empty__', 'label' => 'Sin estado'],
        ];

        $filterArmaOptions = array_merge(
            [['value' => 'todos', 'label' => 'Todos']],
            $siNoOptions,
        );

        $filterAptoOptions = array_merge(
            [['value' => 'todos', 'label' => 'Todos']],
            $siNoOptions,
        );

        $filterFichaEstadoOptions = [
            ['value' => EmployeeFichaProfile::STATUS_ACTIVO, 'label' => 'Activos en ficha'],
            ['value' => EmployeeFichaProfile::STATUS_DESVINCULADO, 'label' => 'Desvinculados'],
            ['value' => 'todos', 'label' => 'Todos (ficha)'],
        ];

        $filterQuery = $this->activeMatrizFilterQuery($filters);
        $errorBag = $request->session()->get('errors');
        $showImportModal = $canEdit
            && $errorBag !== null
            && $errorBag->has('import_file');

        return view('areas.gestion_humana.mt_st_04.matriz', [
            'subTabs' => $this->getMtSt04SubTabs('matriz'),
            'canEdit' => $canEdit,
            'filters' => $filters,
            'filterEstado1Options' => $estado1Options,
            'filterEstado2Options' => $estado2Options,
            'filterArmaOptions' => $filterArmaOptions,
            'filterAptoOptions' => $filterAptoOptions,
            'filterFichaEstadoOptions' => $filterFichaEstadoOptions,
            'siNoOptions' => $siNoOptions,
            'lookupUrl' => route('gestion-humana.mt-st-04.matriz.lookup'),
            'datatableUrl' => route(
                'gestion-humana.mt-st-04.matriz.datatable',
                $filterQuery,
            ),
            'exportUrl' => route('gestion-humana.mt-st-04.matriz.export', $filterQuery),
            'importTemplateUrl' => route('gestion-humana.mt-st-04.matriz.import-template'),
            'importUrl' => route('gestion-humana.mt-st-04.matriz.import'),
            'storeUrl' => route('gestion-humana.mt-st-04.matriz.store'),
            'showImportModal' => $showImportModal,
        ]);
    }

    public function exportMatriz(Request $request): StreamedResponse
    {
        abort_unless($this->mtSt04Access->canView(auth()->user()), 403);

        $filters = $this->matrizFiltersFromRequest($request);
        $registros = $this->listService->all($filters);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'export',
            metadata: [
                'rows' => $registros->count(),
                'filters' => $filters,
            ],
            userId: (int) auth()->id(),
        );

        return MtSt04Export::downloadCollection($registros);
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->mtSt04Access->canEdit(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function importMatriz(ImportMtSt04Request $request): RedirectResponse
    {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path, $request->user()?->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()])->withInput();
        }

        $message = sprintf(
            'Importación finalizada: %d nuevos, %d actualizados.',
            $stats['imported'],
            $stats['updated'],
        );

        if ($stats['empty_rows'] > 0) {
            $message .= sprintf(' %d filas vacías ignoradas.', $stats['empty_rows']);
        }

        if ($stats['duplicates_collapsed'] > 0) {
            $message .= sprintf(
                ' %d cédula(s) duplicada(s) en el archivo (última fila ganó).',
                $stats['duplicates_collapsed'],
            );
        }

        if ($stats['skipped'] > 0) {
            $message .= sprintf(' %d filas con error.', $stats['skipped']);
        }

        $this->auditLogService->logEvent(
            eventType: 'import',
            action: 'import_upsert',
            metadata: [
                'imported' => $stats['imported'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'empty_rows' => $stats['empty_rows'],
                'duplicates_collapsed' => $stats['duplicates_collapsed'],
            ],
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.mt-st-04.matriz')
            ->with('status', $message)
            ->with('import_done', true)
            ->with('import_failures', $stats['failures']);
    }

    public function matrizDatatable(Request $request): JsonResponse
    {
        abort_unless($this->mtSt04Access->canView(auth()->user()), 403);

        return $this->datatableService->respond(
            $request,
            $this->matrizFiltersFromRequest($request),
            $this->mtSt04Access->canEdit(auth()->user()),
        );
    }

    public function matrizLookup(Request $request): JsonResponse
    {
        abort_unless($this->mtSt04Access->canEdit(auth()->user()), 403);

        $cedula = trim((string) $request->query('cedula', ''));

        if ($cedula === '') {
            return response()->json(['found' => false]);
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->first([
                'id',
                'document_number',
                'full_name',
                'position_name',
                'work_city_name',
                'cost_center_name',
                'employment_status',
            ]);

        if ($profile === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'document_number' => $profile->document_number,
            'full_name' => $profile->full_name,
            'cargo' => trim((string) ($profile->position_name ?? '')),
            'ciudad' => trim((string) ($profile->work_city_name ?? '')),
            'puesto' => trim((string) ($profile->cost_center_name ?? '')),
            'employment_status' => $profile->employment_status,
            'employee_ficha_profile_id' => $profile->id,
        ]);
    }

    public function storeMatriz(StoreMtSt04RegistroRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $cargo = $this->resolveCargoFromFicha((string) $validated['document_number']);

        $registro = new MtSt04Registro([
            'document_number' => $validated['document_number'],
            'arma' => $validated['arma'] ?? null,
            'fecha_examen_1' => $validated['fecha_examen_1'] ?? null,
            'apto' => $validated['apto'] ?? null,
            'observaciones_1' => $validated['observaciones_1'] ?? null,
            'fecha_examen_2' => $validated['fecha_examen_2'] ?? null,
            'observaciones_2' => $validated['observaciones_2'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->estadoCalculator->applyToModel($registro, $cargo);
        $registro->save();

        $this->auditLogService->logEvent(
            eventType: 'mt_st_04_registro',
            action: 'created',
            metadata: [
                'mt_st_04_registro_id' => $registro->id,
                'document_number' => $registro->document_number,
                'estado_1' => $registro->estado_1,
                'estado_2' => $registro->estado_2,
            ],
            model: $registro,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.mt-st-04.matriz', $this->activeMatrizFilterQuery(
                $this->matrizFiltersFromRequest($request)
            ))
            ->with('status', 'Registro creado correctamente.');
    }

    public function updateMatriz(
        UpdateMtSt04RegistroRequest $request,
        MtSt04Registro $registro,
    ): RedirectResponse {
        $validated = $request->validated();
        $before = $registro->only([
            'document_number',
            'arma',
            'fecha_examen_1',
            'fecha_vencimiento_1',
            'apto',
            'observaciones_1',
            'estado_1',
            'fecha_examen_2',
            'fecha_vencimiento_2',
            'observaciones_2',
            'estado_2',
        ]);

        $registro->fill([
            'document_number' => $validated['document_number'],
            'arma' => $validated['arma'] ?? null,
            'fecha_examen_1' => $validated['fecha_examen_1'] ?? null,
            'apto' => $validated['apto'] ?? null,
            'observaciones_1' => $validated['observaciones_1'] ?? null,
            'fecha_examen_2' => $validated['fecha_examen_2'] ?? null,
            'observaciones_2' => $validated['observaciones_2'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $cargo = $this->resolveCargoFromFicha((string) $registro->document_number);
        $this->estadoCalculator->applyToModel($registro, $cargo);
        $registro->save();

        $this->auditLogService->logModelChange(
            eventType: 'mt_st_04_registro',
            action: 'updated',
            model: $registro,
            before: $before,
            after: $registro->only(array_keys($before)),
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.mt-st-04.matriz', $this->activeMatrizFilterQuery(
                $this->matrizFiltersFromRequest($request)
            ))
            ->with('status', 'Registro actualizado correctamente.');
    }

    public function destroyMatriz(MtSt04Registro $registro): RedirectResponse
    {
        abort_unless($this->mtSt04Access->canEdit(auth()->user()), 403);

        $metadata = [
            'mt_st_04_registro_id' => $registro->id,
            'document_number' => $registro->document_number,
        ];

        $registro->delete();

        $this->auditLogService->logEvent(
            eventType: 'mt_st_04_registro',
            action: 'deleted',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.mt-st-04.matriz')
            ->with('status', 'Registro eliminado.');
    }

    private function resolveCargoFromFicha(string $documentNumber): string
    {
        return trim((string) EmployeeFichaProfile::query()
            ->where('document_number', $documentNumber)
            ->value('position_name'));
    }

    /**
     * @return array{ficha_estado: string}
     */
    private function dashboardFiltersFromRequest(Request $request): array
    {
        return $this->dashboardService->normalizeFilters([
            'ficha_estado' => (string) $request->query('ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO),
        ]);
    }

    /**
     * @return array{
     *     document_number: string,
     *     full_name: string,
     *     q: string,
     *     estado_1: string,
     *     estado_2: string,
     *     arma: string,
     *     apto: string,
     *     ficha_estado: string,
     * }
     */
    private function matrizFiltersFromRequest(Request $request): array
    {
        // En POST/PATCH los filtros de listado viven en query; no mezclar con el body del CRUD.
        $bag = $request->isMethod('GET') ? $request : $request->query();

        $fichaEstado = (string) ($bag['ficha_estado'] ?? EmployeeFichaProfile::STATUS_ACTIVO);
        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
            'todos',
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        return [
            'document_number' => trim((string) ($bag['document_number'] ?? '')),
            'full_name' => trim((string) ($bag['full_name'] ?? '')),
            'q' => trim((string) ($bag['q'] ?? '')),
            'estado_1' => (string) ($bag['estado_1'] ?? 'todos'),
            'estado_2' => (string) ($bag['estado_2'] ?? 'todos'),
            'arma' => (string) ($bag['arma'] ?? 'todos'),
            'apto' => (string) ($bag['apto'] ?? 'todos'),
            'ficha_estado' => $fichaEstado,
        ];
    }

    /**
     * @param  array{
     *     document_number: string,
     *     full_name: string,
     *     q: string,
     *     estado_1: string,
     *     estado_2: string,
     *     arma: string,
     *     apto: string,
     *     ficha_estado: string,
     * }  $filters
     * @return array<string, string>
     */
    private function activeMatrizFilterQuery(array $filters): array
    {
        $query = [];

        if ($filters['document_number'] !== '') {
            $query['document_number'] = $filters['document_number'];
        }
        if ($filters['full_name'] !== '') {
            $query['full_name'] = $filters['full_name'];
        }
        if ($filters['q'] !== '') {
            $query['q'] = $filters['q'];
        }
        if ($filters['estado_1'] !== 'todos') {
            $query['estado_1'] = $filters['estado_1'];
        }
        if ($filters['estado_2'] !== 'todos') {
            $query['estado_2'] = $filters['estado_2'];
        }
        if ($filters['arma'] !== 'todos') {
            $query['arma'] = $filters['arma'];
        }
        if ($filters['apto'] !== 'todos') {
            $query['apto'] = $filters['apto'];
        }
        // Siempre pasar ficha_estado para que DT/export conserven el default (activo).
        $query['ficha_estado'] = $filters['ficha_estado'];

        return $query;
    }
}
