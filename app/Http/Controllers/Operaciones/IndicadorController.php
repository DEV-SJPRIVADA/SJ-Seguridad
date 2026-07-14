<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operaciones\StoreIndicatorCaptureRequest;
use App\Models\AuditLog;
use App\Models\DashboardSummary;
use App\Models\DashboardWeight;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use App\Services\Indicadores\AuditLogService;
use App\Services\Indicadores\Dashboard\OperationsDashboardService;
use App\Services\Indicadores\IndicatorCaptureService;
use App\Services\Indicadores\IndicatorMotherService;
use App\Services\Indicadores\IndicatorReportExporter;
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
        private readonly IndicatorMotherService $motherService,
        private readonly AuditLogService $auditLogService,
        private readonly YearRangeService $yearRangeService,
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

        $capture = $this->captureService->buildShowContext(
            indicator: $indicator,
            year: $selectedYear,
            month: $selectedMonth,
            user: $request->user(),
        );

        return view('areas.operaciones.indicadores.show', array_merge($capture, [
            'subTabs' => IndicadorNavigation::subTabs(),
            'headerFilters' => [
                'years' => $capture['years'],
                'months' => $capture['months'],
                'selectedYear' => $capture['selectedYear'],
                'selectedMonth' => $capture['selectedMonth'],
                'isPeriodClosed' => $capture['isPeriodClosed'],
                'captureUserName' => $capture['captureUserName'],
            ],
        ]));
    }

    public function storeCapture(StoreIndicatorCaptureRequest $request, Indicator $indicator): RedirectResponse
    {
        abort_unless($indicator->is_active, 404);

        $year = $this->yearRangeService->normalize((int) $request->integer('year'));
        $month = $this->normalizeMonth((int) $request->integer('month'));

        $this->captureService->save(
            indicator: $indicator,
            year: $year,
            month: $month,
            form: (array) $request->input('form', []),
            improvement: $request->improvementPayload(),
            user: $request->user(),
        );

        return redirect()
            ->route('indicadores.show', [
                'indicator' => $indicator->code,
                'year' => $year,
                'month' => $month,
            ])
            ->with('status', 'Captura guardada correctamente para el mes seleccionado.');
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
            ->with(['indicator', 'user'])
            ->where('period_id', $period->id)
            ->where('complies', false)
            ->whereDoesntHave('improvement')
            ->get();

        if ($pending->isNotEmpty()) {
            $items = $pending->map(fn (IndicatorCapture $capture) => [
                'indicator' => $capture->indicator?->code,
                'user' => $capture->user?->name,
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
            $auditModel = $afterCollection->first() ?? DashboardWeight::query()->firstOrFail();

            $this->auditLogService->logModelChange(
                eventType: 'dashboard_weights',
                action: 'update',
                model: $auditModel,
                before: $before,
                after: $after,
                reason: $validated['reason']
            );
        });

        return back()->with('status', 'Pesos actualizados.');
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

        $monthly = $this->motherService->getMonthlyData($indicator, $year, $month);
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
            reason: 'Exporte Excel de captura por usuario',
            metadata: [
                'indicator' => $indicator->code,
                'user_id' => $report['user']->id,
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
            reason: 'Exporte PDF de captura por usuario',
            metadata: [
                'indicator' => $indicator->code,
                'user_id' => $report['user']->id,
                'year' => $report['year'],
                'month' => $report['month'],
            ]
        );

        $pdf = Pdf::loadView('areas.operaciones.exports.leader-pdf', $report)->setPaper('a4', 'portrait');

        return $pdf->download(sprintf(
            'captura-%s-%d-%d-%02d.pdf',
            $indicator->code,
            $report['user']->id,
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
        $user = User::query()->findOrFail((int) $request->integer('user_id', $request->user()->id));

        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();
        $capture = null;
        if ($period) {
            $capture = IndicatorCapture::query()
                ->with('improvement')
                ->where('indicator_id', $indicator->id)
                ->where('user_id', $user->id)
                ->where('period_id', $period->id)
                ->first();
        }

        return [
            'indicator' => $indicator,
            'user' => $user,
            'operations_leader' => $user,
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

        return [
            'indicator' => $indicator,
            'year' => $year,
            'month' => $month,
            'monthly' => $this->motherService->getMonthlyData($indicator, $year, $month),
        ];
    }

    private function normalizeMonth(int $month): int
    {
        $months = config('indicators.months');

        return array_key_exists($month, $months) ? $month : (int) now()->month;
    }
}
