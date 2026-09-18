<?php

namespace App\Services\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DevelopmentRequestDashboardService
{
    /**
     * @param  array{status?: string|null, area_key?: string|null, priority?: string|null}  $filters
     * @return array{
     *     kpis: array{recibidos: int, en_curso: int, entregados: int, vencidos_sla: int},
     *     by_status: array<string, int>,
     *     overdue: list<array{id: int, code: string|null, title: string, priority: string, days_open: int, sla_days: int}>
     * }
     */
    public function build(array $filters = []): array
    {
        $base = $this->filteredQuery($filters);

        $recibidos = (clone $base)
            ->whereNotNull('radicated_at')
            ->count();

        $enCurso = (clone $base)
            ->whereIn('status', [
                DevelopmentRequest::STATUS_RADICADO,
                DevelopmentRequest::STATUS_EN_ANALISIS,
                DevelopmentRequest::STATUS_EN_DESARROLLO,
                DevelopmentRequest::STATUS_EN_PRUEBAS,
                DevelopmentRequest::STATUS_ENTREGADO,
                DevelopmentRequest::STATUS_DEVUELTO,
            ])
            ->count();

        $entregados = (clone $base)
            ->whereIn('status', [
                DevelopmentRequest::STATUS_ENTREGADO,
                DevelopmentRequest::STATUS_CERRADO,
            ])
            ->count();

        $analysisOpen = (clone $base)
            ->whereIn('status', [
                DevelopmentRequest::STATUS_RADICADO,
                DevelopmentRequest::STATUS_EN_ANALISIS,
            ])
            ->whereNotNull('radicated_at')
            ->get();

        $overdueModels = $analysisOpen->filter(fn (DevelopmentRequest $r): bool => $this->isOverdueForAnalysis($r));
        $overdue = $overdueModels
            ->map(function (DevelopmentRequest $r): array {
                $priority = $this->effectivePriority($r);
                $sla = $this->slaDaysFor($priority);
                $daysOpen = $r->radicated_at?->startOfDay()->diffInDays(now()->startOfDay()) ?? 0;

                return [
                    'id' => $r->id,
                    'code' => $r->code,
                    'title' => $r->title,
                    'priority' => $priority,
                    'days_open' => (int) $daysOpen,
                    'sla_days' => $sla,
                ];
            })
            ->values()
            ->all();

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v): int => (int) $v)
            ->all();

        return [
            'kpis' => [
                'recibidos' => $recibidos,
                'en_curso' => $enCurso,
                'entregados' => $entregados,
                'vencidos_sla' => count($overdue),
            ],
            'by_status' => $byStatus,
            'overdue' => $overdue,
        ];
    }

    /**
     * @param  array{status?: string|null, area_key?: string|null, priority?: string|null}  $filters
     * @return Builder<DevelopmentRequest>
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = DevelopmentRequest::query()->with(['creator', 'leader', 'assignedProgrammer']);

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['area_key'] ?? null)) {
            $query->where('area_key', $filters['area_key']);
        }

        if (filled($filters['priority'] ?? null)) {
            $priority = (string) $filters['priority'];
            $query->where(function (Builder $q) use ($priority): void {
                $q->where('tic_confirmed_priority', $priority)
                    ->orWhere(function (Builder $inner) use ($priority): void {
                        $inner->whereNull('tic_confirmed_priority')
                            ->where('suggested_priority', $priority);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array{status?: string|null, area_key?: string|null, priority?: string|null}  $filters
     * @return Collection<int, DevelopmentRequest>
     */
    public function exportRows(array $filters = []): Collection
    {
        return $this->filteredQuery($filters)
            ->latest('id')
            ->limit(5000)
            ->get();
    }

    public function isOverdueForAnalysis(DevelopmentRequest $request, ?CarbonInterface $asOf = null): bool
    {
        if (! in_array($request->status, [
            DevelopmentRequest::STATUS_RADICADO,
            DevelopmentRequest::STATUS_EN_ANALISIS,
        ], true)) {
            return false;
        }

        if ($request->radicated_at === null) {
            return false;
        }

        $asOf ??= now();
        $daysOpen = $request->radicated_at->startOfDay()->diffInDays($asOf->copy()->startOfDay());
        $sla = $this->slaDaysFor($this->effectivePriority($request));

        return $daysOpen > $sla;
    }

    public function effectivePriority(DevelopmentRequest $request): string
    {
        return (string) ($request->tic_confirmed_priority ?: $request->suggested_priority ?: 'importante');
    }

    public function slaDaysFor(string $priority): int
    {
        /** @var array<string, int> $map */
        $map = config('development-requests.sla_analysis_days', []);

        return (int) ($map[$priority] ?? $map['importante'] ?? 5);
    }
}
