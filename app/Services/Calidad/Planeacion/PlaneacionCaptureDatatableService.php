<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlaneacionCaptureDatatableService
{
    public function __construct(
        private readonly PlaneacionConsolidadoService $consolidado,
        private readonly PlaneacionPeriodService $periods,
    ) {}

    /**
     * @return Builder<QualityPlanningIndicator>
     */
    public function filteredQuery(User $user, bool $canEditIndicadores, bool $ordered = true): Builder
    {
        $query = QualityPlanningIndicator::query()
            ->with(['currentPublishedVersion', 'versions'])
            ->whereNull('archived_at')
            ->whereNotNull('current_published_version_id');

        if (! $canEditIndicadores) {
            $today = now()->toDateString();
            $query->where(function (Builder $inner) use ($user, $today): void {
                $inner->whereHas(
                    'assignees',
                    fn (Builder $assignees) => $assignees
                        ->where('user_id', $user->id)
                        ->where('can_capture', true)
                );

                if ($user->hasAssignedArea()) {
                    $inner->orWhereHas(
                        'areaAssignees',
                        fn (Builder $areas) => $areas
                            ->where('area_key', $user->area_key)
                            ->where('can_capture', true)
                    );
                }

                // Suplencia vigente: aparece en Mis indicadores aunque no sea asignado directo.
                $inner->orWhereHas(
                    'substitutions',
                    fn (Builder $substitutions) => $substitutions
                        ->where('substitute_user_id', $user->id)
                        ->whereDate('starts_on', '<=', $today)
                        ->whereDate('ends_on', '>=', $today)
                );
            });
        }

        if ($ordered) {
            $query->orderByDesc('id');
        }

        return $query;
    }

    public function respond(Request $request, User $user, bool $canEditIndicadores, ?int $year = null): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        $year ??= (int) now()->year;

        $baseQuery = $this->filteredQuery($user, $canEditIndicadores, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();
        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        }

        // El estado anual se calcula solo para las filas de esta página.
        $items = $query->get();
        $summary = $this->consolidado->yearSummary($items->all(), $user, $year);

        $data = $items
            ->map(fn (QualityPlanningIndicator $indicator): array => $this->formatRow(
                $indicator,
                $summary[(int) $indicator->id] ?? null,
            ))
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @param  Builder<QualityPlanningIndicator>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('code', 'like', $like)
                ->orWhereHas('versions', fn (Builder $versions) => $versions->where('name', 'like', $like));
        });
    }

    /**
     * @param  Builder<QualityPlanningIndicator>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('code', $direction),
            default => $query->orderByDesc('id'),
        };
    }

    /**
     * @param  array<string, mixed>|null  $summary
     * @return list<string>
     */
    private function formatRow(QualityPlanningIndicator $indicator, ?array $summary): array
    {
        $published = $indicator->currentPublishedVersion;
        $captured = is_array($summary['captured'] ?? null) ? $summary['captured'] : [];
        $pending = is_array($summary['pending'] ?? null) ? $summary['pending'] : [];
        $closed = is_array($summary['closed'] ?? null) ? $summary['closed'] : [];

        return [
            e((string) $indicator->code),
            e((string) ($published?->name ?: '—')),
            e($published ? 'v'.$published->version_number : '—'),
            e((string) count($captured).' de 12'),
            $this->formatPendingCell($pending, $closed),
            $this->formatLastResultCell($indicator, $summary),
            $this->formatActionsCell($indicator),
        ];
    }

    /**
     * @param  list<int>  $pending
     * @param  list<int>  $closed
     */
    private function formatPendingCell(array $pending, array $closed): string
    {
        if ($pending !== []) {
            return '<span class="status-pill status-pill--warning">Pendiente: '
                .e($this->monthList($pending)).'</span>';
        }

        if ($closed !== []) {
            return '<span class="status-pill status-pill--muted">Cerrado: '
                .e($this->monthList($closed)).'</span>';
        }

        return '<span class="status-pill status-pill--success">Al día</span>';
    }

    /**
     * @param  array<string, mixed>|null  $summary
     */
    private function formatLastResultCell(QualityPlanningIndicator $indicator, ?array $summary): string
    {
        $result = $summary['last_result'] ?? null;

        if ($result === null) {
            return '—';
        }

        $suffix = $indicator->currentPublishedVersion?->unit === QualityPlanningIndicatorVersion::UNIT_PERCENTAGE ? '%' : '';
        $month = (int) ($summary['last_month'] ?? 0);
        $monthLabel = $month > 0 ? (PlaneacionConsolidadoService::MONTH_ABBREV[$month] ?? '') : '';

        $value = e(rtrim(rtrim(number_format((float) $result, 2, '.', ''), '0'), '.').$suffix);

        return $value.($monthLabel !== ''
            ? ' <span class="planeacion-mis-indicadores__month">'.e($monthLabel).'</span>'
            : '');
    }

    private function formatActionsCell(QualityPlanningIndicator $indicator): string
    {
        $captureUrl = route('calidad.planeacion.captura.show', $indicator);

        return '<div class="table-actions">'.
            '<a href="'.e($captureUrl).'" class="cursos-catalogo-page__icon-btn" title="Capturar" aria-label="Capturar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>'.
            '</a></div>';
    }

    /**
     * @param  list<int>  $months
     */
    private function monthList(array $months): string
    {
        return implode(', ', array_map(
            fn (int $month): string => PlaneacionConsolidadoService::MONTH_ABBREV[$month] ?? (string) $month,
            $months,
        ));
    }
}
