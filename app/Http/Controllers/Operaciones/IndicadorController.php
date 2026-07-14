<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Requests\Operaciones\StoreIndicatorCaptureRequest;
use App\Http\Requests\Operaciones\StoreIndicatorSystemDocumentRequest;
use App\Http\Requests\Operaciones\StoreIndicatorSystemDocumentVersionRequest;
use App\Http\Requests\Operaciones\UpdateIndicatorSystemDocumentRequest;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DashboardSummary;
use App\Models\DashboardWeight;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\IndicatorSystemDocument;
use App\Models\OperationsLeader;
use App\Models\Period;
use App\Services\Indicadores\DocumentationService;
use App\Services\Indicadores\IndicatorCaptureService;
use App\Services\Indicadores\IndicatorReportExporter;
use App\Services\Indicadores\AuditLogService;
use App\Services\Indicadores\Dashboard\OperationsDashboardService;
use App\Services\Indicadores\Dashboard\ZoneDashboardService;
use App\Services\Indicadores\IndicatorMotherService;
use App\Services\Indicadores\YearRangeService;
use App\Support\IndicadorNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IndicadorController extends Controller
{
    public function __construct(
        private readonly OperationsDashboardService $dashboardService,
        private readonly ZoneDashboardService $leaderDashboardService,
        private readonly IndicatorMotherService $motherService,
        private readonly AuditLogService $auditLogService,
        private readonly YearRangeService $yearRangeService,
        private readonly DocumentationService $documentationService,
        private readonly IndicatorReportExporter $reportExporter,
        private readonly IndicatorCaptureService $captureService,
    ) {
    }

    public function dashboard(Request $request): View
    {
        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));
        $dashboard = $this->dashboardService->build($year, $month);
        $summary = DashboardSummary::query()->where(['year' => $year, 'month' => $month])->first();

        $this->auditLogService->logEvent(
            eventType: 'admin_action',
            action: 'dashboard_view',
            reason: 'Consulta dashboard general de operaciones',
            metadata: ['year' => $year, 'month' => $month]
        );

        return view('areas.operaciones.dashboard.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'year' => $year,
            'month' => $month,
            'years' => $this->yearRangeService->years(),
            'months' => config('indicators.months'),
            'dashboard' => $dashboard,
            'summary' => $summary,
        ]);
    }

    public function index(): View
    {
        $indicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();

        return view('areas.operaciones.indicadores.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'indicators' => $indicators,
        ]);
    }

    public function show(Request $request, Indicator $indicator): View
    {
        abort_unless($indicator->is_active, 404);

        $selectedYear = $this->yearRangeService->normalize((int) $request->integer('year', (int) now()->year));
        $selectedMonth = $this->normalizeMonth((int) $request->integer('month', (int) now()->month));
        $selectedLeaderId = (int) $request->integer('operations_leader_id', 0);

        $capture = $this->captureService->buildShowContext(
            indicator: $indicator,
            year: $selectedYear,
            month: $selectedMonth,
            operationsLeaderId: $selectedLeaderId > 0 ? $selectedLeaderId : null,
        );

        return view('areas.operaciones.indicadores.show', array_merge($capture, [
            'subTabs' => IndicadorNavigation::subTabs(),
            'headerFilters' => [
                'years' => $capture['years'],
                'months' => $capture['months'],
                'leaders' => $capture['operationsLeaders'],
                'selectedYear' => $capture['selectedYear'],
                'selectedMonth' => $capture['selectedMonth'],
                'selectedOperationsLeaderId' => (int) ($capture['selectedOperationsLeaderId'] ?? 0),
                'isPeriodClosed' => $capture['isPeriodClosed'],
            ],
        ]));
    }

    public function storeCapture(StoreIndicatorCaptureRequest $request, Indicator $indicator): RedirectResponse
    {
        abort_unless($indicator->is_active, 404);

        $year = $this->yearRangeService->normalize((int) $request->integer('year'));
        $month = $this->normalizeMonth((int) $request->integer('month'));
        $leaderId = (int) $request->integer('operations_leader_id');

        $this->captureService->save(
            indicator: $indicator,
            year: $year,
            month: $month,
            operationsLeaderId: $leaderId,
            form: (array) $request->input('form', []),
            improvement: $request->improvementPayload(),
            user: $request->user(),
        );

        return redirect()
            ->route('indicadores.show', [
                'indicator' => $indicator->code,
                'year' => $year,
                'month' => $month,
                'operations_leader_id' => $leaderId,
            ])
            ->with('status', 'Captura guardada correctamente para el mes seleccionado.');
    }

    public function leadersIndex(): View
    {
        $leaders = OperationsLeader::query()->orderBy('name')->paginate(20);

        return view('areas.operaciones.leaders.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'leaders' => $leaders,
        ]);
    }

    public function leaderStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:operations_leaders,code'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $leader = OperationsLeader::query()->create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'operations_leader',
            action: 'create',
            model: $leader,
            before: null,
            after: $leader->toArray(),
            reason: 'Creacion de jefe de operaciones'
        );

        return back()->with('status', 'Jefe de operaciones creado correctamente.');
    }

    public function leaderUpdate(Request $request, OperationsLeader $operationsLeader): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('operations_leaders', 'code')->ignore($operationsLeader->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $before = $operationsLeader->toArray();

        $operationsLeader->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active', $operationsLeader->is_active),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'operations_leader',
            action: 'update',
            model: $operationsLeader,
            before: $before,
            after: $operationsLeader->fresh()->toArray(),
            reason: 'Actualizacion de jefe de operaciones'
        );

        return back()->with('status', 'Jefe de operaciones actualizado.');
    }

    public function leaderShow(Request $request, OperationsLeader $operationsLeader): View
    {
        abort_unless($operationsLeader->is_active, 404);

        $selectedYear = $this->yearRangeService->normalize((int) $request->integer('year', (int) now()->year));
        $selectedMonth = $this->normalizeMonth((int) $request->integer('month', (int) now()->month));
        $dashboard = $this->leaderDashboardService->build($operationsLeader, $selectedYear, $selectedMonth);

        return view('areas.operaciones.leaders.show', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'leader' => $operationsLeader,
            'dashboard' => $dashboard,
            'years' => $this->yearRangeService->years(),
            'months' => config('indicators.months'),
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
        ]);
    }

    public function periods(): View
    {
        $periods = Period::query()->orderByDesc('year')->orderByDesc('month')->paginate(24);

        return view('areas.operaciones.periodos.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'periods' => $periods,
            'years' => $this->yearRangeService->years(),
            'months' => config('indicators.months'),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => $this->yearRangeService->validationRules(),
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'status' => ['required', Rule::in([Period::STATUS_OPEN, Period::STATUS_CLOSED])],
        ]);

        $period = Period::query()->firstOrCreate(
            ['year' => $validated['year'], 'month' => $validated['month']],
            ['status' => $validated['status']]
        );

        if (! $period->wasRecentlyCreated) {
            return back()->with('status', 'El periodo ya existe.');
        }

        $this->auditLogService->logModelChange(
            eventType: 'period',
            action: 'create',
            model: $period,
            before: null,
            after: $period->toArray(),
            reason: 'Creacion de periodo'
        );

        return back()->with('status', 'Periodo creado correctamente.');
    }

    public function closePeriod(Request $request, Period $period): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $pending = IndicatorCapture::query()
            ->with(['indicator', 'operationsLeader'])
            ->where('period_id', $period->id)
            ->where('complies', false)
            ->whereDoesntHave('improvement')
            ->get();

        if ($pending->isNotEmpty()) {
            $items = $pending->map(fn (IndicatorCapture $capture) => [
                'indicator' => $capture->indicator?->code,
                'leader' => $capture->operationsLeader?->code,
                'result' => $capture->result_percentage,
            ])->all();

            return back()
                ->withErrors(['close' => 'No se puede cerrar: existen indicadores en rojo sin mejora diligenciada.'])
                ->with('pending_improvements', $items);
        }

        $before = $period->toArray();

        $period->update([
            'status' => Period::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by_user_id' => auth()->id(),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'period',
            action: 'close',
            model: $period,
            before: $before,
            after: $period->fresh()->toArray(),
            reason: $validated['reason']
        );

        return back()->with('status', 'Periodo cerrado. La edicion de capturas queda bloqueada.');
    }

    public function reopenPeriod(Request $request, Period $period): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $before = $period->toArray();

        $period->update([
            'status' => Period::STATUS_OPEN,
            'reopened_at' => now(),
            'reopened_by_user_id' => auth()->id(),
            'reopen_reason' => $validated['reason'],
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'period',
            action: 'reopen',
            model: $period,
            before: $before,
            after: $period->fresh()->toArray(),
            reason: $validated['reason']
        );

        return back()->with('status', 'Periodo reabierto correctamente.');
    }

    public function weights(): View
    {
        $indicators = Indicator::query()
            ->orderBy('code')
            ->with('dashboardWeight')
            ->get();

        return view('areas.operaciones.configuracion.pesos', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'indicators' => $indicators,
        ]);
    }

    public function updateWeights(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'numeric', 'min:0', 'max:100'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated): void {
            $before = DashboardWeight::query()->with('indicator')->get()->toArray();

            foreach ($validated['weights'] as $indicatorId => $weight) {
                DashboardWeight::query()->updateOrCreate(
                    ['indicator_id' => (int) $indicatorId],
                    [
                        'weight' => $weight,
                        'updated_by_user_id' => auth()->id(),
                    ]
                );
            }

            $afterCollection = DashboardWeight::query()->with('indicator')->get();
            $after = $afterCollection->toArray();

            $content = $afterCollection
                ->sortBy(fn (DashboardWeight $item) => $item->indicator?->code)
                ->map(fn (DashboardWeight $item) => ($item->indicator?->code ?? 'N/A').': '.$item->weight.'%')
                ->implode("\n");

            $version = $this->documentationService->upsertDashboardWeightsDocument($content, $validated['reason']);

            $this->auditLogService->logModelChange(
                eventType: 'dashboard_weights',
                action: 'update',
                model: $version->document,
                before: $before,
                after: $after,
                reason: $validated['reason'],
                metadata: ['document_version_id' => $version->id]
            );
        });

        return back()->with('status', 'Pesos actualizados y versionados en documentacion.');
    }

    public function documents(): View
    {
        $documents = IndicatorSystemDocument::query()
            ->with(['indicator', 'currentVersion'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('areas.operaciones.documentos.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'documents' => $documents,
        ]);
    }

    public function createDocument(): View
    {
        $indicators = Indicator::query()->orderBy('code')->get();

        return view('areas.operaciones.documentos.create', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'indicators' => $indicators,
        ]);
    }

    public function storeDocument(StoreIndicatorSystemDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $document = IndicatorSystemDocument::query()->create([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'scope' => $validated['scope'],
            'indicator_id' => $validated['indicator_id'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $version = $this->documentationService->createVersion(
            document: $document,
            content: $validated['content'],
            status: $validated['initial_status'],
            changeSummary: $validated['change_summary'],
            changeReason: $validated['change_reason']
        );

        $this->auditLogService->logModelChange(
            eventType: 'document',
            action: 'create',
            model: $document,
            before: null,
            after: $document->fresh()->load('currentVersion')->toArray(),
            reason: $validated['change_reason'],
            metadata: ['document_version_id' => $version->id]
        );

        return redirect()->route('indicadores.admin.documents.index')->with('status', 'Documento creado correctamente.');
    }

    public function showDocument(IndicatorSystemDocument $indicatorSystemDocument): View
    {
        $indicatorSystemDocument->load(['indicator', 'currentVersion', 'versions.author']);

        return view('areas.operaciones.documentos.show', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'document' => $indicatorSystemDocument,
        ]);
    }

    public function editDocument(IndicatorSystemDocument $indicatorSystemDocument): View
    {
        $indicators = Indicator::query()->orderBy('code')->get();

        return view('areas.operaciones.documentos.edit', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'document' => $indicatorSystemDocument,
            'indicators' => $indicators,
        ]);
    }

    public function updateDocument(UpdateIndicatorSystemDocumentRequest $request, IndicatorSystemDocument $indicatorSystemDocument): RedirectResponse
    {
        $before = $indicatorSystemDocument->toArray();
        $validated = $request->validated();

        $indicatorSystemDocument->update([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'scope' => $validated['scope'],
            'indicator_id' => $validated['indicator_id'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'document',
            action: 'update',
            model: $indicatorSystemDocument,
            before: $before,
            after: $indicatorSystemDocument->fresh()->toArray(),
            reason: $validated['reason']
        );

        return redirect()
            ->route('indicadores.admin.documents.show', $indicatorSystemDocument)
            ->with('status', 'Documento actualizado.');
    }

    public function destroyDocument(IndicatorSystemDocument $indicatorSystemDocument): RedirectResponse
    {
        $before = $indicatorSystemDocument->load('versions')->toArray();
        $indicatorSystemDocument->delete();

        $this->auditLogService->logModelChange(
            eventType: 'document',
            action: 'delete',
            model: $indicatorSystemDocument,
            before: $before,
            after: null,
            reason: 'Eliminacion de documento'
        );

        return redirect()->route('indicadores.admin.documents.index')->with('status', 'Documento eliminado.');
    }

    public function storeDocumentVersion(StoreIndicatorSystemDocumentVersionRequest $request, IndicatorSystemDocument $indicatorSystemDocument): RedirectResponse
    {
        $before = $indicatorSystemDocument->load('currentVersion')->toArray();
        $validated = $request->validated();

        $version = $this->documentationService->createVersion(
            document: $indicatorSystemDocument,
            content: $validated['content'],
            status: $validated['status'],
            changeSummary: $validated['change_summary'],
            changeReason: $validated['change_reason']
        );

        $this->auditLogService->logModelChange(
            eventType: 'document_version',
            action: 'create',
            model: $indicatorSystemDocument,
            before: $before,
            after: $indicatorSystemDocument->fresh()->load('currentVersion')->toArray(),
            reason: $validated['change_reason'],
            metadata: ['document_version_id' => $version->id]
        );

        return redirect()
            ->route('indicadores.admin.documents.show', $indicatorSystemDocument)
            ->with('status', 'Nueva version registrada correctamente.');
    }

    public function mother(): View
    {
        $indicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();

        return view('areas.operaciones.madre.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'indicators' => $indicators,
        ]);
    }

    public function motherShow(Request $request, Indicator $indicator): View
    {
        abort_unless($indicator->is_active, 404);

        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));
        $zones = OperationsLeader::query()->where('is_active', true)->orderBy('name')->get();

        $monthly = $this->motherService->getMonthlyData($indicator, $year, $month, $zones);
        $quarterly = $indicator->code === 'FT-OP-08'
            ? $this->motherService->getQuarterlyDataFtOp08($year)
            : null;

        $this->auditLogService->logEvent(
            eventType: 'admin_action',
            action: 'mother_view',
            reason: 'Consulta consolidado MADRE',
            metadata: ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]
        );

        return view('areas.operaciones.madre.show', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'indicator' => $indicator,
            'year' => $year,
            'month' => $month,
            'years' => $this->yearRangeService->years(),
            'months' => config('indicators.months'),
            'monthly' => $monthly,
            'quarterly' => $quarterly,
        ]);
    }

    public function auditLog(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('event_type'), fn ($query) => $query->where('event_type', $request->string('event_type')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $eventTypes = AuditLog::query()->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type');
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('areas.operaciones.auditoria.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'logs' => $logs,
            'eventTypes' => $eventTypes,
            'actions' => $actions,
        ]);
    }

    public function saveSummary(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => $this->yearRangeService->validationRules(),
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'summary_text' => ['required', 'string'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $summary = DashboardSummary::query()->firstOrNew(['year' => $year, 'month' => $month]);
        $before = $summary->exists ? $summary->toArray() : null;

        if (! $summary->exists) {
            $summary->generated_by_user_id = auth()->id();
        }

        $summary->summary_text = $validated['summary_text'];
        $summary->updated_by_user_id = auth()->id();
        $summary->save();

        $this->auditLogService->logModelChange(
            eventType: 'dashboard_summary',
            action: 'save',
            model: $summary,
            before: $before,
            after: $summary->fresh()->toArray(),
            reason: 'Actualizacion de resumen ejecutivo',
            metadata: ['year' => $year, 'month' => $month]
        );

        return redirect()
            ->route('indicadores.dashboard', ['year' => $year, 'month' => $month])
            ->with('status', 'Resumen ejecutivo guardado.');
    }

    public function exportDashboardPdf(Request $request)
    {
        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));
        $dashboard = $this->dashboardService->build($year, $month);
        $summary = DashboardSummary::query()->where(['year' => $year, 'month' => $month])->first();

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'dashboard_pdf',
            reason: 'Exporte PDF dashboard ejecutivo',
            metadata: ['year' => $year, 'month' => $month]
        );

        $pdf = Pdf::loadView('areas.operaciones.dashboard.pdf', compact('year', 'month', 'dashboard', 'summary'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('dashboard-ejecutivo-'.$year.'-'.$month.'.pdf');
    }

    public function exportLeaderExcel(Request $request, Indicator $indicator)
    {
        abort_unless($indicator->is_active, 404);
        $report = $this->buildLeaderReport($request, $indicator);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'leader_excel',
            reason: 'Exporte Excel por jefe de operaciones',
            metadata: [
                'indicator' => $indicator->code,
                'operations_leader_id' => $report['operations_leader']->id,
                'year' => $report['year'],
                'month' => $report['month'],
            ]
        );

        return $this->reportExporter->leaderExcelResponse($report);
    }

    public function exportLeaderPdf(Request $request, Indicator $indicator)
    {
        abort_unless($indicator->is_active, 404);
        $report = $this->buildLeaderReport($request, $indicator);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'leader_pdf',
            reason: 'Exporte PDF por jefe de operaciones',
            metadata: [
                'indicator' => $indicator->code,
                'operations_leader_id' => $report['operations_leader']->id,
                'year' => $report['year'],
                'month' => $report['month'],
            ]
        );

        $pdf = Pdf::loadView('areas.operaciones.exports.leader-pdf', $report)->setPaper('a4', 'portrait');

        return $pdf->download(sprintf(
            'jefe-%s-%s-%d-%02d.pdf',
            $indicator->code,
            $report['operations_leader']->code,
            $report['year'],
            $report['month']
        ));
    }

    public function exportMotherExcel(Request $request, Indicator $indicator)
    {
        abort_unless($indicator->is_active, 404);
        $report = $this->buildMotherReport($request, $indicator);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'mother_excel',
            reason: 'Exporte Excel consolidado MADRE',
            metadata: ['indicator' => $indicator->code, 'year' => $report['year'], 'month' => $report['month']]
        );

        return $this->reportExporter->motherExcelResponse($report);
    }

    public function exportMotherPdf(Request $request, Indicator $indicator)
    {
        abort_unless($indicator->is_active, 404);
        $report = $this->buildMotherReport($request, $indicator);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'mother_pdf',
            reason: 'Exporte PDF consolidado MADRE',
            metadata: ['indicator' => $indicator->code, 'year' => $report['year'], 'month' => $report['month']]
        );

        $pdf = Pdf::loadView('areas.operaciones.exports.mother-pdf', $report)->setPaper('a4', 'landscape');

        return $pdf->download(sprintf('madre-%s-%d-%02d.pdf', $indicator->code, $report['year'], $report['month']));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLeaderReport(Request $request, Indicator $indicator): array
    {
        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));
        $leaderId = (int) $request->integer('operations_leader_id');
        $leader = OperationsLeader::query()->findOrFail($leaderId);

        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();
        $capture = null;
        if ($period) {
            $capture = IndicatorCapture::query()
                ->with('improvement')
                ->where('indicator_id', $indicator->id)
                ->where('operations_leader_id', $leader->id)
                ->where('period_id', $period->id)
                ->first();
        }

        return [
            'indicator' => $indicator,
            'operations_leader' => $leader,
            'year' => $year,
            'month' => $month,
            'capture' => $capture,
            'display' => $capture?->input_data ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMotherReport(Request $request, Indicator $indicator): array
    {
        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));
        $zones = OperationsLeader::query()->where('is_active', true)->orderBy('name')->get();

        return [
            'indicator' => $indicator,
            'year' => $year,
            'month' => $month,
            'monthly' => $this->motherService->getMonthlyData($indicator, $year, $month, $zones),
        ];
    }

    private function normalizeMonth(int $month): int
    {
        $months = config('indicators.months');

        return array_key_exists($month, $months) ? $month : (int) now()->month;
    }
}
