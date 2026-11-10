<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlaneacionCaptureDatatableService
{
    /**
     * @return Builder<QualityPlanningIndicator>
     */
    public function filteredQuery(User $user, bool $canEditIndicadores, bool $ordered = true): Builder
    {
        $query = QualityPlanningIndicator::query()
            ->with(['currentPublishedVersion'])
            ->whereNull('archived_at')
            ->whereNotNull('current_published_version_id');

        if (! $canEditIndicadores) {
            $query->whereHas(
                'assignees',
                fn (Builder $assignees) => $assignees
                    ->where('user_id', $user->id)
                    ->where('can_capture', true)
            );
        }

        if ($ordered) {
            $query->orderByDesc('id');
        }

        return $query;
    }

    public function respond(Request $request, User $user, bool $canEditIndicadores): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = $this->filteredQuery($user, $canEditIndicadores, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();
        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        }

        $data = $query->get()
            ->map(fn (QualityPlanningIndicator $indicator): array => $this->formatRow($indicator))
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
     * @return list<string>
     */
    private function formatRow(QualityPlanningIndicator $indicator): array
    {
        $published = $indicator->currentPublishedVersion;
        $formUrl = route('calidad.planeacion.captura.show', $indicator);

        return [
            e((string) $indicator->code),
            e((string) ($published?->name ?: '—')),
            e($published ? 'v'.$published->version_number : '—'),
            '<div class="table-actions">'.
            '<a href="'.e($formUrl).'" class="cursos-catalogo-page__icon-btn" title="Capturar" aria-label="Capturar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>'.
            '</a></div>',
        ];
    }
}
