<?php

namespace App\Http\Controllers\GestionHumana;

use App\Exports\FormacionExport;
use App\Exports\FormacionImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ImportFormacionRequest;
use App\Models\FormacionRegistro;
use App\Services\Access\FormacionAccessService;
use App\Services\GestionHumana\FormacionAuditLogService;
use App\Services\GestionHumana\FormacionDashboardService;
use App\Services\GestionHumana\FormacionDatatableService;
use App\Services\GestionHumana\FormacionImportService;
use App\Traits\HasFormacionTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormacionController extends Controller
{
    use HasFormacionTabs;

    public function __construct(
        private readonly FormacionAccessService $formacionAccess,
        private readonly FormacionDatatableService $datatableService,
        private readonly FormacionDashboardService $dashboardService,
        private readonly FormacionAuditLogService $auditLogService,
        private readonly FormacionImportTemplateExport $importTemplateExport,
        private readonly FormacionImportService $importService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        return redirect()->route('gestion-humana.formacion.dashboard', $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $anio = $filters['anio'] !== '' ? (int) $filters['anio'] : null;
        $payload = $this->dashboardService->metrics($anio, $filters);
        $mes = $payload['filters']['mes'] !== '' ? (int) $payload['filters']['mes'] : null;
        $options = $this->dashboardService->filterSelectOptions($payload['anio'], $mes);

        return view('areas.gestion_humana.formacion.dashboard', [
            'subTabs' => $this->getFormacionSubTabs('dashboard'),
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.formacion.dashboard.metrics'),
            'yearOptions' => $options['anios'],
            'monthOptions' => $options['meses'],
            'estadoOptions' => $options['estados'],
            'cursoOptions' => $payload['options']['cursos'] ?? $options['cursos'],
        ]);
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        $filters = $this->dashboardFiltersFromRequest($request);
        $anio = $filters['anio'] !== '' ? (int) $filters['anio'] : null;

        return response()->json(
            $this->dashboardService->metrics($anio, $filters)
        );
    }

    public function formaciones(Request $request): View
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        $filters = $this->filtersFromRequest($request);
        $options = $this->datatableService->filterSelectOptions();
        $activeQuery = $this->activeFilterQuery($filters);

        $canEdit = $this->formacionAccess->canEdit(auth()->user());
        $errorBag = $request->session()->get('errors');
        $showImportModal = $canEdit
            && $errorBag !== null
            && ($errorBag->has('import_file') || $errorBag->has('confirm_replace'));

        return view('areas.gestion_humana.formacion.formaciones', [
            'subTabs' => $this->getFormacionSubTabs('formaciones'),
            'canEdit' => $canEdit,
            'showImportModal' => $showImportModal,
            'filters' => $filters,
            'filterAnioOptions' => $options['anios'],
            'filterMesOptions' => $options['meses'],
            'filterCategoriaOptions' => $options['categorias'],
            'filterCursoOptions' => $options['cursos'],
            'filterEstadoOptions' => $options['estados'],
            'datatableUrl' => route('gestion-humana.formacion.formaciones.datatable', $activeQuery),
            'exportUrl' => route('gestion-humana.formacion.formaciones.export', $activeQuery),
            'importTemplateUrl' => route('gestion-humana.formacion.formaciones.import-template'),
            'importUrl' => route('gestion-humana.formacion.formaciones.import'),
        ]);
    }

    public function formacionesDatatable(Request $request): JsonResponse
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        return $this->datatableService->respond(
            $request,
            $this->filtersFromRequest($request),
        );
    }

    public function formacionesOptions(): JsonResponse
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        return response()->json($this->datatableService->filterSelectOptions());
    }

    public function formacionesExport(Request $request): StreamedResponse
    {
        abort_unless($this->formacionAccess->canView(auth()->user()), 403);

        $filters = $this->filtersFromRequest($request);
        $rows = $this->datatableService->filteredQuery($filters)->get();

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'formaciones_excel',
            metadata: [
                'row_count' => $rows->count(),
                'filters' => array_filter(
                    $filters,
                    static fn ($value): bool => $value !== null && $value !== '',
                ),
            ],
            userId: (int) auth()->id(),
        );

        return FormacionExport::downloadCollection($rows);
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless($this->formacionAccess->canEdit(auth()->user()), 403);

        return $this->importTemplateExport->download();
    }

    public function import(ImportFormacionRequest $request): RedirectResponse
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
            'Importación completada: %d registro(s) cargado(s). Se eliminaron %d registro(s) previos. Filas vacías omitidas: %d.',
            $stats['imported'],
            $stats['deleted_before'],
            $stats['skipped_empty'],
        );

        return redirect()
            ->route('gestion-humana.formacion.formaciones')
            ->with('status', $message);
    }

    /**
     * @return array{
     *     anio: string,
     *     mes: string,
     *     categoria: string,
     *     nombre_curso: string,
     *     numero_id: string,
     *     nombre: string,
     *     estado: string,
     * }
     */
    private function filtersFromRequest(Request $request): array
    {
        $estado = strtolower(trim((string) $request->query('estado', '')));
        if ($estado !== '' && ! array_key_exists($estado, FormacionRegistro::ESTADO_LABELS)) {
            $estado = '';
        }

        return [
            'anio' => trim((string) $request->query('anio', '')),
            'mes' => trim((string) $request->query('mes', '')),
            'categoria' => trim((string) $request->query('categoria', '')),
            'nombre_curso' => trim((string) $request->query('nombre_curso', '')),
            'numero_id' => trim((string) $request->query('numero_id', '')),
            'nombre' => trim((string) $request->query('nombre', '')),
            'estado' => $estado,
        ];
    }

    /**
     * @return array{
     *     anio: string,
     *     mes: string,
     *     estado: string,
     *     nombre_curso: string,
     * }
     */
    private function dashboardFiltersFromRequest(Request $request): array
    {
        $anio = $this->dashboardAnioFromRequest($request);
        $mesRaw = trim((string) $request->query('mes', ''));
        $mes = ($mesRaw !== '' && ctype_digit($mesRaw) && (int) $mesRaw >= 1 && (int) $mesRaw <= 12)
            ? (string) ((int) $mesRaw)
            : '';
        $estado = strtolower(trim((string) $request->query('estado', '')));
        if ($estado !== '' && ! array_key_exists($estado, FormacionRegistro::ESTADO_LABELS)) {
            $estado = '';
        }

        return [
            'anio' => $anio !== null ? (string) $anio : '',
            'mes' => $mes,
            'estado' => $estado,
            'nombre_curso' => trim((string) $request->query('nombre_curso', '')),
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

    /**
     * @param  array<string, string>  $filters
     * @return array<string, string>
     */
    private function activeFilterQuery(array $filters): array
    {
        return array_filter(
            $filters,
            static fn (string $value): bool => $value !== '',
        );
    }
}
