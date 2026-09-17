<?php

namespace App\Http\Controllers\DevelopmentRequests;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevelopmentRequests\StoreDevelopmentRequestMessageRequest;
use App\Http\Requests\DevelopmentRequests\TicTransitionDevelopmentRequestRequest;
use App\Http\Requests\DevelopmentRequests\UatDevelopmentRequestRequest;
use App\Models\DevelopmentRequest;
use App\Models\User;
use App\Services\DevelopmentRequests\DevelopmentRequestDashboardService;
use App\Services\DevelopmentRequests\DevelopmentRequestNotificationService;
use App\Services\DevelopmentRequests\DevelopmentRequestWorkflowService;
use App\Traits\HasDevelopmentRequestTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevelopmentRequestTicController extends Controller
{
    use HasDevelopmentRequestTabs;

    public function index(Request $request, string $module, DevelopmentRequestDashboardService $dashboard): View
    {
        $filters = $this->filtersFromRequest($request);
        $metrics = $dashboard->build($filters);

        $query = $dashboard->filteredQuery($filters);

        if (! filled($filters['status'])) {
            $query->whereIn('status', [
                DevelopmentRequest::STATUS_RADICADO,
                DevelopmentRequest::STATUS_EN_ANALISIS,
                DevelopmentRequest::STATUS_EN_DESARROLLO,
                DevelopmentRequest::STATUS_EN_PRUEBAS,
                DevelopmentRequest::STATUS_ENTREGADO,
                DevelopmentRequest::STATUS_DEVUELTO,
            ]);
        }

        $requests = $query
            ->latest()
            ->limit(200)
            ->get();

        return view('modules.development-requests.tic-queue', [
            'module' => $module,
            'subTabs' => $this->getDevelopmentRequestSubTabs($module),
            'requests' => $requests,
            'kpis' => $metrics['kpis'],
            'overdue' => $metrics['overdue'],
            'filters' => $filters,
            'statusOptions' => $this->statusFilterOptions(),
            'areaOptions' => $this->areaFilterOptions(),
            'priorityOptions' => $this->priorityFilterOptions(),
        ]);
    }

    public function export(Request $request, string $module, DevelopmentRequestDashboardService $dashboard): StreamedResponse
    {
        $filters = $this->filtersFromRequest($request);
        $rows = $dashboard->exportRows($filters)->map(fn (DevelopmentRequest $item): array => [
            'code' => $item->code,
            'title' => $item->title,
            'area' => $item->areaLabel(),
            'status' => $item->estadoLabel(),
            'tipo' => $item->tipoLabel(),
            'priority' => $item->prioridadLabel(),
            'tic_priority' => $item->tic_confirmed_priority
                ? (DevelopmentRequest::prioridadesLabels()[$item->tic_confirmed_priority] ?? $item->tic_confirmed_priority)
                : '',
            'requester' => $item->requester_name,
            'leader' => $item->leader?->name,
            'programmer' => $item->assignedProgrammer?->name,
            'radicated_at' => $item->radicated_at?->format('Y-m-d H:i'),
            'estimated' => $item->tic_estimated_date?->format('Y-m-d'),
            'uat' => $item->uat_result,
        ]);

        $columns = [
            ['label' => 'Codigo', 'key' => 'code'],
            ['label' => 'Titulo', 'key' => 'title'],
            ['label' => 'Area', 'key' => 'area'],
            ['label' => 'Estado', 'key' => 'status'],
            ['label' => 'Tipo', 'key' => 'tipo'],
            ['label' => 'Prioridad solicitada', 'key' => 'priority'],
            ['label' => 'Prioridad TIC', 'key' => 'tic_priority'],
            ['label' => 'Solicitante', 'key' => 'requester'],
            ['label' => 'Lider', 'key' => 'leader'],
            ['label' => 'Programador', 'key' => 'programmer'],
            ['label' => 'Radicado', 'key' => 'radicated_at'],
            ['label' => 'Fecha estimada', 'key' => 'estimated'],
            ['label' => 'UAT', 'key' => 'uat'],
        ];

        return (new BaseExport(
            $rows,
            $columns,
            'solicitudes_desarrollo_'.now()->format('Ymd_His').'.xlsx',
            'Solicitudes de desarrollo',
        ))->download();
    }

    public function transition(
        TicTransitionDevelopmentRequestRequest $request,
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestWorkflowService $workflow,
    ): RedirectResponse {
        Gate::authorize('view', $developmentRequest);

        $validated = $request->validated();
        $ticFields = collect($validated)->only([
            'assigned_programmer_id',
            'tic_viability',
            'tic_confirmed_priority',
            'tic_complexity',
            'requires_fo_ge_12',
            'requires_extended_analysis',
            'tic_estimated_date',
            'tic_risks',
            'tic_analysis_notes',
            'closure_notes',
        ])->all();

        try {
            $workflow->transition(
                $developmentRequest,
                $request->user(),
                (string) $validated['to_status'],
                $validated['comment'] ?? null,
                $ticFields,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['to_status' => $e->getMessage()]);
        }

        return back()->with('status', 'Estado actualizado.');
    }

    public function storeMessage(
        StoreDevelopmentRequestMessageRequest $request,
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestNotificationService $notifications,
    ): RedirectResponse {
        Gate::authorize('comment', $developmentRequest);

        $message = $developmentRequest->messages()->create([
            'user_id' => $request->user()->id,
            'body' => trim((string) $request->validated('body')),
        ]);

        $notifications->notifyMessageParticipants($developmentRequest, $message, $request->user());

        return redirect()
            ->route('development-requests.show', [
                'module' => $module,
                'development_request' => $developmentRequest,
                'from' => request()->query('from', 'tic_queue'),
            ])
            ->withFragment('conversation')
            ->with('status', 'Mensaje enviado.');
    }

    public function uat(
        UatDevelopmentRequestRequest $request,
        string $module,
        DevelopmentRequest $developmentRequest,
        DevelopmentRequestWorkflowService $workflow,
    ): RedirectResponse {
        $validated = $request->validated();
        $uatOk = $validated['uat_result'] === 'si';

        $developmentRequest->uat_result = $validated['uat_result'];
        $developmentRequest->uat_notes = $validated['uat_notes'] ?? null;
        $developmentRequest->save();

        try {
            $workflow->transition(
                $developmentRequest,
                $request->user(),
                $uatOk ? DevelopmentRequest::STATUS_ENTREGADO : DevelopmentRequest::STATUS_EN_DESARROLLO,
                $validated['uat_notes'] ?? ($uatOk ? 'UAT aceptado' : 'UAT no aceptado'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['uat_result' => $e->getMessage()]);
        }

        return back()->with('status', $uatOk ? 'UAT aceptado. Solicitud entregada.' : 'UAT rechazado; vuelve a desarrollo.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function programmerOptions(): array
    {
        $permission = 'devreq.tab.tic_queue';

        if (! Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->exists()) {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->permission($permission)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u): array => ['value' => (string) $u->id, 'label' => $u->name])
            ->values()
            ->all();
    }

    /**
     * @return array{status: string|null, area_key: string|null, priority: string|null}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'status' => $request->query('status') ?: null,
            'area_key' => $request->query('area_key') ?: null,
            'priority' => $request->query('priority') ?: null,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusFilterOptions(): array
    {
        return collect(DevelopmentRequest::estadosLabels())
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function areaFilterOptions(): array
    {
        return collect(config('access.areas', []))
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function priorityFilterOptions(): array
    {
        return collect(DevelopmentRequest::prioridadesLabels())
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
