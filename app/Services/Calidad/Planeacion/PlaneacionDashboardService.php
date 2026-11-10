<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PlaneacionDashboardService
{
    public function __construct(
        private readonly PlaneacionEstrategicaAccessService $access,
        private readonly PlaneacionConsolidadoService $consolidado,
        private readonly PlaneacionPeriodService $periods,
    ) {}

    /**
     * KPI y series del mes consolidado, acotados al perfil (editor vs consulta).
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user, int $year, int $month): array
    {
        $indicators = $this->indicatorsInScope($user);
        $rows = [];
        $assignedSlots = 0;
        $capturedSlots = 0;
        $green = 0;
        $red = 0;
        $pending = 0;
        $complianceLabels = [];
        $complianceValues = [];
        $pendingLabels = [];
        $pendingValues = [];

        foreach ($indicators as $indicator) {
            $snapshot = $this->consolidado->monthSnapshot($indicator, $year, $month, null);
            $assignees = $indicator->assignees
                ->filter(fn ($row): bool => (bool) $row->can_capture && $row->user && (bool) $row->user->is_active);
            $assigned = $assignees->count();
            $capturedIds = array_flip($snapshot['captured_user_ids']);
            $captured = $assignees->filter(fn ($row): bool => isset($capturedIds[(int) $row->user_id]))->count();

            $assignedSlots += $assigned;
            $capturedSlots += $captured;

            $status = 'pendiente';
            if ($snapshot['has_data'] && $snapshot['complies']) {
                $status = 'verde';
                $green++;
            } elseif ($snapshot['has_data']) {
                $status = 'rojo';
                $red++;
            } else {
                $pending++;
            }

            $label = $indicator->code;
            $complianceLabels[] = $label;
            $complianceValues[] = $snapshot['result'] !== null ? round((float) $snapshot['result'], 2) : 0;
            if ($status === 'pendiente') {
                $pendingLabels[] = $label;
                $pendingValues[] = 1;
            }

            $rows[] = [
                'id' => $indicator->id,
                'code' => $indicator->code,
                'name' => $snapshot['name'],
                'result' => $snapshot['result'],
                'result_display' => $snapshot['result'] !== null
                    ? $this->consolidado->formatValue($snapshot['result'])
                    : '—',
                'target' => $snapshot['target'],
                'target_display' => $snapshot['operator'] !== '' && $snapshot['target'] !== null
                    ? $snapshot['operator'].' '.$this->consolidado->formatValue($snapshot['target'])
                    : '—',
                'status' => $status,
                'status_label' => match ($status) {
                    'verde' => 'Cumple',
                    'rojo' => 'No cumple',
                    default => 'Pendiente',
                },
                'has_data' => $snapshot['has_data'],
                'url' => route('calidad.planeacion.indicadores.show', [
                    'indicator' => $indicator,
                    'year' => $year,
                    'month' => $month,
                ]),
            ];
        }

        if ($pendingLabels === []) {
            $pendingLabels = ['Sin pendientes'];
            $pendingValues = [0];
        }

        $percentCaptured = $assignedSlots > 0
            ? round(($capturedSlots / $assignedSlots) * 100, 1)
            : ($capturedSlots > 0 ? 100.0 : 0.0);

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => mb_convert_case($this->periods->monthLabel($month), MB_CASE_TITLE, 'UTF-8'),
            'kpis' => [
                'indicadores' => $indicators->count(),
                'pct_capturados' => $percentCaptured,
                'asignados' => $assignedSlots,
                'capturados' => $capturedSlots,
                'verde' => $green,
                'rojo' => $red,
                'pendientes' => $pending,
            ],
            'rows' => $rows,
            'charts' => [
                'cumplimiento' => [
                    'labels' => $complianceLabels,
                    'data' => $complianceValues,
                ],
                'pendientes' => [
                    'labels' => $pendingLabels,
                    'data' => $pendingValues,
                ],
            ],
        ];
    }

    /**
     * @return Collection<int, QualityPlanningIndicator>
     */
    public function indicatorsInScope(User $user): Collection
    {
        $query = QualityPlanningIndicator::query()
            ->whereNull('archived_at')
            ->whereNotNull('current_published_version_id')
            ->with(['versions', 'currentPublishedVersion', 'assignees.user'])
            ->orderBy('code');

        if (! $this->access->canEditIndicadores($user)) {
            $query->whereHas(
                'assignees',
                fn (Builder $assignees) => $assignees
                    ->where('user_id', $user->id)
                    ->where('can_consult', true),
            );
        }

        return $query->get();
    }
}
