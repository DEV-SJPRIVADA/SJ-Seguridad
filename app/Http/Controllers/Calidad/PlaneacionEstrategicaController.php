<?php

namespace App\Http\Controllers\Calidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calidad\Planeacion\PreviewPlaneacionFormulaRequest;
use App\Http\Requests\Calidad\Planeacion\StorePlaneacionCaptureRequest;
use App\Http\Requests\Calidad\Planeacion\StorePlaneacionIndicatorRequest;
use App\Http\Requests\Calidad\Planeacion\SyncPlaneacionAssigneesRequest;
use App\Http\Requests\Calidad\Planeacion\UpdatePlaneacionIndicatorRequest;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use App\Services\Calidad\Planeacion\FormulaEvaluator;
use App\Services\Calidad\Planeacion\PlaneacionAssignmentService;
use App\Services\Calidad\Planeacion\PlaneacionCaptureDatatableService;
use App\Services\Calidad\Planeacion\PlaneacionCaptureService;
use App\Services\Calidad\Planeacion\PlaneacionConsolidadoService;
use App\Services\Calidad\Planeacion\PlaneacionDashboardService;
use App\Services\Calidad\Planeacion\PlaneacionFichaExportService;
use App\Services\Calidad\Planeacion\PlaneacionIndicatorDatatableService;
use App\Services\Calidad\Planeacion\PlaneacionIndicatorService;
use App\Services\Calidad\Planeacion\PlaneacionPeriodService;
use App\Traits\HasPlaneacionTabs;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlaneacionEstrategicaController extends Controller
{
    use HasPlaneacionTabs;

    public function __construct(
        private readonly PlaneacionEstrategicaAccessService $access,
        private readonly PlaneacionIndicatorService $indicators,
        private readonly PlaneacionIndicatorDatatableService $datatable,
        private readonly PlaneacionAssignmentService $assignments,
        private readonly PlaneacionPeriodService $periods,
        private readonly PlaneacionCaptureService $captures,
        private readonly PlaneacionCaptureDatatableService $captureDatatable,
        private readonly PlaneacionConsolidadoService $consolidado,
        private readonly PlaneacionDashboardService $dashboardMetrics,
        private readonly PlaneacionFichaExportService $fichaExport,
        private readonly FormulaEvaluator $evaluator,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->access->canViewBoard(auth()->user()), 403);

        $tabs = $this->access->visibleTabsFor(auth()->user());
        $firstTab = $tabs[0] ?? null;
        abort_unless($firstTab !== null, 403);

        $route = match ($firstTab) {
            'indicadores' => 'calidad.planeacion.indicadores',
            'captura' => 'calidad.planeacion.captura',
            default => 'calidad.planeacion.dashboard',
        };

        return redirect()->route($route, $request->query());
    }

    public function dashboard(Request $request): View
    {
        abort_unless($this->access->canViewDashboard(auth()->user()), 403);

        [$year, $month] = $this->periodFromRequest($request);
        $metrics = $this->dashboardMetrics->metrics(auth()->user(), $year, $month);

        return view('areas.calidad.planeacion.dashboard', [
            'subTabs' => $this->getPlaneacionSubTabs('dashboard'),
            'year' => $year,
            'month' => $month,
            'yearOptions' => $this->periods->yearOptions($year),
            'monthOptions' => $this->periods->monthOptions(),
            'metrics' => $metrics,
        ]);
    }

    public function indicadores(Request $request): View
    {
        abort_unless($this->access->canViewIndicadores(auth()->user()), 403);

        $filters = $this->datatable->filtersFromRequest($request);

        return view('areas.calidad.planeacion.indicadores', [
            'subTabs' => $this->getPlaneacionSubTabs('indicadores'),
            'filters' => $filters,
            'canEdit' => $this->access->canEditIndicadores(auth()->user()),
            'statusOptions' => $this->statusOptions(),
            'datatableUrl' => route('calidad.planeacion.indicadores.datatable', array_filter($filters)),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->access->canViewIndicadores(auth()->user()), 403);

        $user = auth()->user();
        $canEdit = $this->access->canEditIndicadores($user);

        return $this->datatable->respond(
            $request,
            $this->datatable->filtersFromRequest($request),
            $user,
            $canEdit,
        );
    }

    public function create(): View
    {
        abort_unless($this->access->canEditIndicadores(auth()->user()), 403);

        return view('areas.calidad.planeacion.form', [
            'subTabs' => $this->getPlaneacionSubTabs('indicadores'),
            'indicator' => null,
            'version' => null,
            'canEdit' => true,
            'options' => $this->formOptions(),
            'previewUrl' => route('calidad.planeacion.indicadores.preview'),
            'shareUsers' => [],
        ]);
    }

    public function store(StorePlaneacionIndicatorRequest $request): RedirectResponse
    {
        $indicator = $this->indicators->create($request->validated(), (int) auth()->id());

        return redirect()
            ->route('calidad.planeacion.indicadores.show', $indicator)
            ->with('status', 'Indicador '.$indicator->code.' guardado como borrador.');
    }

    public function show(Request $request, QualityPlanningIndicator $indicator): View
    {
        $this->authorizeFichaView($indicator);

        $indicator->load(['versions', 'currentPublishedVersion', 'assignees.user']);
        $version = $indicator->workingVersion();
        abort_unless($version instanceof QualityPlanningIndicatorVersion, 404);

        [$year, $month] = $this->periodFromRequest($request);
        $consultVersionId = $this->versionIdFromRequest($request, $indicator);
        $ficha = $this->fichaForRequest($request, $indicator);

        $canManage = $this->access->canEditIndicadores(auth()->user());
        $canEdit = $canManage && ! $indicator->isArchived();
        $consultVersion = $consultVersionId !== null
            ? $indicator->versions->firstWhere('id', $consultVersionId)
            : null;

        return view('areas.calidad.planeacion.show', [
            'subTabs' => $this->getPlaneacionSubTabs('indicadores'),
            'indicator' => $indicator,
            'version' => $version,
            'consultVersion' => $consultVersion instanceof QualityPlanningIndicatorVersion ? $consultVersion : null,
            'versionHistory' => $this->indicators->versionHistory($indicator),
            'versionOptions' => $this->versionOptions($indicator),
            'canEdit' => $canEdit,
            'canRestore' => $canManage && $indicator->isArchived(),
            'year' => $year,
            'month' => $month,
            'yearOptions' => $this->periods->yearOptions($year),
            'monthOptions' => $this->periods->monthOptions(),
            'ficha' => $ficha,
            'shareUsers' => $canEdit ? $this->shareUserOptions() : [],
        ]);
    }

    public function edit(QualityPlanningIndicator $indicator): View
    {
        abort_unless($this->access->canEditIndicadores(auth()->user()), 403);
        abort_if($indicator->isArchived(), 403);

        $indicator->load(['versions', 'currentPublishedVersion', 'assignees.user']);
        $version = $indicator->workingVersion();
        abort_unless($version instanceof QualityPlanningIndicatorVersion, 404);

        return view('areas.calidad.planeacion.form', [
            'subTabs' => $this->getPlaneacionSubTabs('indicadores'),
            'indicator' => $indicator,
            'version' => $version,
            'canEdit' => true,
            'options' => $this->formOptions(),
            'previewUrl' => route('calidad.planeacion.indicadores.preview'),
            'shareUsers' => $this->shareUserOptions(),
            'versionHistory' => $this->indicators->versionHistory($indicator),
        ]);
    }

    public function update(UpdatePlaneacionIndicatorRequest $request, QualityPlanningIndicator $indicator): RedirectResponse
    {
        $indicator = $this->indicators->update($indicator, $request->validated(), (int) auth()->id());

        return redirect()
            ->route('calidad.planeacion.indicadores.show', $indicator)
            ->with('status', 'Indicador '.$indicator->code.' actualizado.');
    }

    public function publish(QualityPlanningIndicator $indicator): RedirectResponse
    {
        abort_unless($this->access->canEditIndicadores(auth()->user()), 403);

        $indicator = $this->indicators->publish($indicator, (int) auth()->id());

        return redirect()
            ->route('calidad.planeacion.indicadores.show', $indicator)
            ->with('status', 'Indicador '.$indicator->code.' publicado.');
    }

    public function archive(QualityPlanningIndicator $indicator): RedirectResponse
    {
        abort_unless($this->access->canEditIndicadores(auth()->user()), 403);

        $this->indicators->archive($indicator, (int) auth()->id());

        return redirect()
            ->route('calidad.planeacion.indicadores')
            ->with('status', 'Indicador '.$indicator->code.' archivado.');
    }

    public function restore(QualityPlanningIndicator $indicator): RedirectResponse
    {
        abort_unless($this->access->canEditIndicadores(auth()->user()), 403);

        $this->indicators->restore($indicator, (int) auth()->id());

        return redirect()
            ->route('calidad.planeacion.indicadores.show', $indicator)
            ->with('status', 'Indicador '.$indicator->code.' restablecido.');
    }

    public function exportFichaExcel(Request $request, QualityPlanningIndicator $indicator): StreamedResponse
    {
        $this->authorizeFichaView($indicator);
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $this->fichaExport->excel($indicator, $this->fichaForRequest($request, $indicator), $user);
    }

    public function exportFichaPdf(Request $request, QualityPlanningIndicator $indicator): Response
    {
        $this->authorizeFichaView($indicator);
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $this->fichaExport->pdf($indicator, $this->fichaForRequest($request, $indicator), $user);
    }

    public function share(SyncPlaneacionAssigneesRequest $request, QualityPlanningIndicator $indicator): RedirectResponse
    {
        abort_if($indicator->isArchived(), 403);

        $this->assignments->sync($indicator, $request->assigneesPayload(), (int) auth()->id());

        return redirect()
            ->back()
            ->with('status', 'Usuarios del indicador '.$indicator->code.' actualizados.');
    }

    public function previewFormula(PreviewPlaneacionFormulaRequest $request): JsonResponse
    {
        $formula = (string) $request->validated('formula');
        $values = $request->sampleValues();
        $errors = $this->evaluator->validate($formula, array_keys($values));

        if ($errors !== []) {
            return response()->json(['message' => $errors[0], 'errors' => $errors], 422);
        }

        try {
            $result = $this->evaluator->evaluate($formula, $values);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['result' => $result]);
    }

    public function captura(): View
    {
        abort_unless($this->access->canViewCapture(auth()->user()), 403);

        return view('areas.calidad.planeacion.captura', [
            'subTabs' => $this->getPlaneacionSubTabs('captura'),
            'datatableUrl' => route('calidad.planeacion.captura.datatable'),
        ]);
    }

    public function capturaDatatable(Request $request): JsonResponse
    {
        abort_unless($this->access->canViewCapture(auth()->user()), 403);

        $user = auth()->user();

        return $this->captureDatatable->respond(
            $request,
            $user,
            $this->access->canEditIndicadores($user),
        );
    }

    public function capturaShow(Request $request, QualityPlanningIndicator $indicator): View
    {
        abort_unless($this->access->canCaptureIndicator(auth()->user(), $indicator), 403);

        [$year, $month] = $this->periodFromRequest($request);

        $period = $this->periods->versionForCapture($indicator, $year, $month);
        $version = $period->version;
        $existing = null;
        $result = null;
        $complies = false;
        $input = [];

        if ($version instanceof QualityPlanningIndicatorVersion) {
            $existing = $this->captures->existingCapture($version, auth()->user(), $year, $month);
            $input = is_array($existing?->input_data) ? $existing->input_data : [];
            if ($existing !== null) {
                $result = $existing->result_value !== null ? (float) $existing->result_value : null;
                $complies = (bool) $existing->complies;
            }
        }

        $fieldLabels = [];
        if ($version instanceof QualityPlanningIndicatorVersion) {
            foreach ($version->fieldLabelPairs() as $pair) {
                $fieldLabels[$pair['key']] = $pair['label'];
            }
        }

        return view('areas.calidad.planeacion.captura-form', [
            'subTabs' => $this->getPlaneacionSubTabs('captura'),
            'indicator' => $indicator,
            'version' => $version,
            'year' => $year,
            'month' => $month,
            'yearOptions' => $this->periods->yearOptions($year),
            'monthOptions' => $this->periods->monthOptions(),
            'periodClosed' => $period->closed,
            'periodMessage' => $period->message,
            'canSave' => $this->access->canEditCapture(auth()->user()) && $period->isCapturable(),
            'existingCapture' => $existing,
            'input' => $input,
            'result' => $result,
            'complies' => $complies,
            'formulaDisplay' => $version instanceof QualityPlanningIndicatorVersion
                ? $this->evaluator->displayWithLabels($version->formula, $fieldLabels)
                : '',
            'ficha' => $this->consolidado->ficha(
                $indicator,
                auth()->user(),
                $year,
                $month,
                (int) auth()->id(),
                true,
            ),
        ]);
    }

    public function capturaStore(StorePlaneacionCaptureRequest $request, QualityPlanningIndicator $indicator): RedirectResponse
    {
        $capture = $this->captures->save($indicator, $request->user(), $request->validated());
        $monthLabel = $this->periods->monthLabel((int) $capture->month);

        return redirect()
            ->route('calidad.planeacion.captura.show', [
                'indicator' => $indicator,
                'year' => $capture->year,
                'month' => $capture->month,
            ])
            ->with('status', 'Captura de '.$monthLabel.' '.$capture->year.' guardada.');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function periodFromRequest(Request $request): array
    {
        $year = (int) $request->integer('year', (int) now()->year);
        $month = (int) $request->integer('month', (int) now()->month);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return [$year, $month];
    }

    private function authorizeFichaView(QualityPlanningIndicator $indicator): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        if ($this->access->canConsultIndicator($user, $indicator)) {
            return;
        }

        abort_unless($this->access->canCaptureIndicator($user, $indicator), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function fichaForRequest(Request $request, QualityPlanningIndicator $indicator): array
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $indicator->loadMissing(['versions', 'currentPublishedVersion', 'assignees.user']);
        [$year, $month] = $this->periodFromRequest($request);
        $canConsult = $this->access->canConsultIndicator($user, $indicator);
        $forceOwn = ! $canConsult;

        return $this->consolidado->ficha(
            $indicator,
            $user,
            $year,
            $month,
            $forceOwn ? (int) $user->id : $this->capturadorIdFromRequest($request),
            $forceOwn,
            $forceOwn ? null : $this->versionIdFromRequest($request, $indicator),
        );
    }

    private function capturadorIdFromRequest(Request $request): ?int
    {
        $raw = $request->input('capturador_id');
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    private function versionIdFromRequest(Request $request, QualityPlanningIndicator $indicator): ?int
    {
        $raw = $request->query('version');
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;
        if ($id < 1) {
            return null;
        }

        return $indicator->versions->contains(fn ($version): bool => (int) $version->id === $id)
            ? $id
            : null;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function versionOptions(QualityPlanningIndicator $indicator): array
    {
        $options = [
            ['value' => '0', 'label' => 'Vigente (según el mes)'],
        ];

        foreach ($indicator->versions->sortBy('version_number') as $version) {
            $options[] = [
                'value' => (string) $version->id,
                'label' => 'v'.$version->version_number.' · '.$version->statusLabel(),
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Todos'],
            ['value' => QualityPlanningIndicatorVersion::STATUS_DRAFT, 'label' => 'Borrador'],
            ['value' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED, 'label' => 'Publicado'],
            ['value' => 'archived', 'label' => 'Archivado'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function shareUserOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'value' => (string) $user->id,
                'label' => (string) $user->name,
            ])
            ->all();
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function formOptions(): array
    {
        $processes = [];
        foreach (config('quality-documents.processes', []) as $key => $label) {
            $processes[] = ['value' => (string) $key, 'label' => (string) $label];
        }

        return [
            'processes' => $processes,
            'units' => [
                ['value' => 'percentage', 'label' => 'Porcentaje'],
                ['value' => 'number', 'label' => 'Número'],
            ],
            'frequencies' => [
                ['value' => QualityPlanningIndicatorVersion::FREQUENCY_MONTHLY, 'label' => 'Mensual'],
            ],
            'operators' => [
                ['value' => '>=', 'label' => 'Mayor o igual (≥)'],
                ['value' => '<=', 'label' => 'Menor o igual (≤)'],
                ['value' => '>', 'label' => 'Mayor que (>)'],
                ['value' => '<', 'label' => 'Menor que (<)'],
                ['value' => '=', 'label' => 'Igual (=)'],
            ],
        ];
    }
}
