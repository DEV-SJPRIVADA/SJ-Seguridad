<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operaciones\StoreIndicatorCaptureRequest;
use App\Models\AuditLog;
use App\Models\DashboardSummary;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use App\Services\Indicadores\AuditLogService;
use App\Services\Indicadores\Dashboard\OperationsDashboardService;
use App\Services\Indicadores\IndicatorCaptureService;
use App\Services\Indicadores\IndicatorConsolidadoService;
use App\Services\Indicadores\IndicatorReportExporter;
use App\Services\Indicadores\ManagementReport\ManagementReportDataBuilder;
use App\Services\Indicadores\ManagementReport\ManagementReportPptxExporter;
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
        private readonly IndicatorConsolidadoService $consolidadoService,
        private readonly AuditLogService $auditLogService,
        private readonly YearRangeService $yearRangeService,
        private readonly IndicatorReportExporter $reportExporter,
        private readonly IndicatorCaptureService $captureService,
        private readonly ManagementReportDataBuilder $managementReportDataBuilder,
        private readonly ManagementReportPptxExporter $managementReportPptxExporter,
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

    public function ajustes(Request $request): View
    {
        $section = (string) $request->query('section', 'periodos');

        if (! in_array($section, ['periodos', 'metas', 'auditoria'], true)) {
            $section = 'periodos';
        }

        $data = [
            'subTabs' => IndicadorNavigation::subTabs(),
            'section' => $section,
            'years' => $this->yearRangeService->years(),
            'months' => config('indicators.months'),
        ];

        if ($section === 'periodos') {
            $data['periods'] = Period::query()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(24)
                ->withQueryString();
        }

        if ($section === 'metas') {
            $data['indicators'] = Indicator::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get();
        }

        if ($section === 'auditoria') {
            $data['logs'] = AuditLog::query()
                ->with('user')
                ->when($request->filled('event_type'), fn ($query) => $query->where('event_type', $request->string('event_type')))
                ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
                ->orderByDesc('created_at')
                ->paginate(30)
                ->withQueryString();

            $data['eventTypes'] = AuditLog::query()->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type');
            $data['actions'] = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        }

        return view('areas.operaciones.ajustes.index', $data);
    }

    public function periods(): RedirectResponse
    {
        return redirect()->route('indicadores.admin.ajustes', ['section' => 'periodos']);
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

    public function metas(): RedirectResponse
    {
        return redirect()->route('indicadores.admin.ajustes', ['section' => 'metas']);
    }

    public function weights(): RedirectResponse
    {
        return redirect()->route('indicadores.admin.ajustes', ['section' => 'metas']);
    }

    public function updateMetas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'operators' => ['required', 'array'],
            'operators.*' => ['required', Rule::in(['>=', '<=', '=='])],
            'metas' => ['required', 'array'],
            'metas.*' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'critical' => ['required', 'array'],
            'critical.*' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated): void {
            $before = Indicator::query()
                ->orderBy('code')
                ->get(['id', 'code', 'target_operator', 'target_value', 'critical_value'])
                ->keyBy('id')
                ->toArray();

            foreach ($validated['metas'] as $indicatorId => $meta) {
                $indicator = Indicator::query()->findOrFail((int) $indicatorId);

                $indicator->update([
                    'target_operator' => $validated['operators'][$indicatorId]
                        ?? $validated['operators'][(string) $indicatorId],
                    'target_value' => $meta,
                    'critical_value' => $validated['critical'][$indicatorId]
                        ?? $validated['critical'][(string) $indicatorId],
                ]);
            }

            $after = Indicator::query()
                ->orderBy('code')
                ->get(['id', 'code', 'target_operator', 'target_value', 'critical_value'])
                ->keyBy('id')
                ->toArray();

            $auditModel = Indicator::query()->orderBy('code')->firstOrFail();

            $this->auditLogService->logModelChange(
                eventType: 'indicator_targets',
                action: 'update',
                model: $auditModel,
                before: $before,
                after: $after,
                reason: $validated['reason']
            );
        });

        return redirect()
            ->route('indicadores.admin.ajustes', ['section' => 'metas'])
            ->with('status', 'Metas actualizadas.');
    }

    public function consolidado(): View
    {
        $indicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();

        return view('areas.operaciones.consolidado.index', [
            'subTabs' => IndicadorNavigation::subTabs(),
            'indicators' => $indicators,
        ]);
    }

    public function consolidadoShow(Request $request, Indicator $indicator): View
    {
        abort_unless($indicator->is_active, 404);

        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));

        $monthly = $this->consolidadoService->getMonthlyData($indicator, $year, $month);
        $quarterly = $indicator->code === 'FT-OP-08'
            ? $this->consolidadoService->getQuarterlyDataFtOp08($year)
            : null;

        $this->auditLogService->logEvent(
            eventType: 'admin_action',
            action: 'consolidado_view',
            reason: 'Consulta consolidado',
            metadata: ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]
        );

        return view('areas.operaciones.consolidado.show', [
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

    public function auditLog(Request $request): RedirectResponse
    {
        return redirect()->route('indicadores.admin.ajustes', array_filter([
            'section' => 'auditoria',
            'event_type' => $request->query('event_type'),
            'action' => $request->query('action'),
        ]));
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

    public function exportManagementPptx(Request $request)
    {
        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));
        $report = $this->managementReportDataBuilder->build($year, $month);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'management_pptx',
            reason: 'Exporte informe de gestion FO-GI-39',
            metadata: ['year' => $year, 'month' => $month]
        );

        return $this->managementReportPptxExporter->downloadResponse($report);
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

    public function exportConsolidadoExcel(Request $request, Indicator $indicator)
    {
        abort_unless($indicator->is_active, 404);
        $report = $this->buildConsolidadoReport($request, $indicator);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'consolidado_excel',
            reason: 'Exporte Excel consolidado',
            metadata: ['indicator' => $indicator->code, 'year' => $report['year'], 'month' => $report['month']]
        );

        return $this->reportExporter->consolidadoExcelResponse($report);
    }

    public function exportConsolidadoPdf(Request $request, Indicator $indicator)
    {
        abort_unless($indicator->is_active, 404);
        $report = $this->buildConsolidadoReport($request, $indicator);

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'consolidado_pdf',
            reason: 'Exporte PDF consolidado',
            metadata: ['indicator' => $indicator->code, 'year' => $report['year'], 'month' => $report['month']]
        );

        $pdf = Pdf::loadView('areas.operaciones.exports.consolidado-pdf', $report)->setPaper('a4', 'landscape');

        return $pdf->download(sprintf('consolidado-%s-%d-%02d.pdf', $indicator->code, $report['year'], $report['month']));
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
    private function buildConsolidadoReport(Request $request, Indicator $indicator): array
    {
        $year = $this->yearRangeService->normalize((int) $request->integer('year', now()->year));
        $month = $this->normalizeMonth((int) $request->integer('month', now()->month));

        return [
            'indicator' => $indicator,
            'year' => $year,
            'month' => $month,
            'monthly' => $this->consolidadoService->getMonthlyData($indicator, $year, $month),
        ];
    }

    private function normalizeMonth(int $month): int
    {
        $months = config('indicators.months');

        return array_key_exists($month, $months) ? $month : (int) now()->month;
    }
}
