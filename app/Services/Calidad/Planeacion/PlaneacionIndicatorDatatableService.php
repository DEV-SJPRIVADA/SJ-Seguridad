<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlaneacionIndicatorDatatableService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<QualityPlanningIndicator>
     */
    public function filteredQuery(array $filters, User $user, bool $canEdit, bool $ordered = true): Builder
    {
        $query = QualityPlanningIndicator::query()
            ->with(['versions', 'currentPublishedVersion']);

        if (! $canEdit) {
            $query->whereNull('archived_at')
                ->whereHas(
                    'assignees',
                    fn (Builder $assignees) => $assignees
                        ->where('user_id', $user->id)
                        ->where('can_consult', true)
                );
        }

        if ($ordered) {
            $query->orderByDesc('id');
        }

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function filtersFromRequest(Request $request): array
    {
        return [
            'status' => $request->string('status')->toString(),
        ];
    }

    /**
     * @param  Builder<QualityPlanningIndicator>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void
    {
        $status = (string) ($filters['status'] ?? '');

        if ($status === 'archived') {
            $query->whereNotNull('archived_at');

            return;
        }

        if ($status === QualityPlanningIndicatorVersion::STATUS_DRAFT) {
            $query->whereNull('archived_at')
                ->whereHas('versions', fn (Builder $versions) => $versions
                    ->where('status', QualityPlanningIndicatorVersion::STATUS_DRAFT));

            return;
        }

        if ($status === QualityPlanningIndicatorVersion::STATUS_PUBLISHED) {
            $query->whereNull('archived_at')
                ->whereNotNull('current_published_version_id');
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, User $user, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = $this->filteredQuery($filters, $user, $canEdit, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();
        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        }

        $data = $query->get()->map(fn (QualityPlanningIndicator $indicator): array => $this->formatRow($indicator, $canEdit))->all();

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
     * @return list<string>
     */
    private function formatRow(QualityPlanningIndicator $indicator, bool $canEdit): array
    {
        $working = $indicator->workingVersion();
        $showUrl = route('calidad.planeacion.indicadores.show', $indicator);

        $cells = [
            e((string) $indicator->code),
            e((string) ($working?->name ?: '—')),
            e($working ? 'v'.$working->version_number : '—'),
            e($this->statusLabel($indicator, $working)),
        ];

        $cells[] = $this->formatActionsCell($indicator, $working, $showUrl, $canEdit);

        return $cells;
    }

    private function statusLabel(QualityPlanningIndicator $indicator, ?QualityPlanningIndicatorVersion $working): string
    {
        if ($indicator->isArchived()) {
            return 'Archivado';
        }

        return $working?->statusLabel() ?? '—';
    }

    private function formatActionsCell(
        QualityPlanningIndicator $indicator,
        ?QualityPlanningIndicatorVersion $working,
        string $showUrl,
        bool $canEdit,
    ): string {
        $html = '<div class="table-actions">'.
            '<a href="'.e($showUrl).'" class="cursos-catalogo-page__icon-btn" title="Consultar" aria-label="Consultar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>'.
            '</a>';

        if ($canEdit && $indicator->isArchived()) {
            $html .= '<form method="POST" action="'.e(route('calidad.planeacion.indicadores.restore', $indicator)).'" class="table-actions__inline">'.
                csrf_field().
                '<button type="submit" class="cursos-catalogo-page__icon-btn" title="Restablecer indicador" aria-label="Restablecer indicador">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>'.
                '</button></form>';
        }

        if ($canEdit && ! $indicator->isArchived()) {
            $editUrl = route('calidad.planeacion.indicadores.edit', $indicator);
            $html .= '<a href="'.e($editUrl).'" class="cursos-catalogo-page__icon-btn" title="Editar" aria-label="Editar">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>'.
                '</a>';

            if ($working?->isDraft()) {
                $html .= '<form method="POST" action="'.e(route('calidad.planeacion.indicadores.publish', $indicator)).'" class="table-actions__inline">'.
                    csrf_field().
                    '<button type="submit" class="cursos-catalogo-page__icon-btn" title="Publicar" aria-label="Publicar">'.
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                    '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>'.
                    '</button></form>';
            }

            $code = (string) $indicator->code;
            $html .= '<form method="POST" action="'.e(route('calidad.planeacion.indicadores.archive', $indicator)).'" class="table-actions__inline js-confirm-archive" '.
                'data-confirm-title="Archivar indicador" '.
                'data-confirm-message="'.e('¿Archivar '.$code.'? Dejará de capturarse y de aparecer en el dashboard hasta que lo restablezca.').'">'.
                csrf_field().
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Archivar" aria-label="Archivar">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<rect width="20" height="5" x="2" y="4" rx="1"/><path d="M4 9v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9"/><path d="M10 13h4"/></svg>'.
                '</button></form>';
        }

        return $html.'</div>';
    }
}
