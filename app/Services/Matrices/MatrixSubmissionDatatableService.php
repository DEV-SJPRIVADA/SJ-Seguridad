<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixField;
use App\Models\Matrices\MatrixSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * DataTables server-side del listado de registros de una matriz.
 */
final class MatrixSubmissionDatatableService
{
    public function __construct(
        private readonly MatrixAccessResolver $accessResolver,
        private readonly MatrixSubmissionFilterService $filters,
        private readonly MatrixSubmissionPresenter $presenter,
    ) {}

    public function respond(Request $request, User $user, Matrix $matrix, string $module): JsonResponse
    {
        $access = $this->accessResolver->resolve($user, $matrix);

        $base = MatrixSubmission::query()
            ->where('matrix_submissions.matrix_id', $matrix->id)
            ->whereNull('matrix_submissions.archived_at')
            ->visibleTo($access->canViewOthers, $user->id);

        $recordsTotal = (clone $base)->count();

        $this->filters->apply($base, $this->filters->fromRequest($request));

        $recordsFiltered = (clone $base)->count();

        $this->applyOrdering($base, $request);

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? 100 : max(1, min(100, $length));

        /** @var Collection<int, MatrixSubmission> $rows */
        $rows = $base
            ->with([
                'creator:id,name',
                'values' => fn ($query) => $query->with('field:id,matrix_version_id,field_key,type,options'),
            ])
            ->skip($start)
            ->take($length)
            ->get();

        $fields = $this->presenter->tableFields($matrix);

        $data = $rows
            ->map(fn (MatrixSubmission $submission): array => $this->formatRow($user, $module, $matrix, $submission, $fields))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @param  Collection<int, MatrixField>  $fields
     * @return array<int, string>
     */
    private function formatRow(User $user, string $module, Matrix $matrix, MatrixSubmission $submission, Collection $fields): array
    {
        $values = $this->presenter->displayMap($submission);

        $row = [
            '<strong>'.e($submission->folio).'</strong>',
            '<span class="matrices-status matrices-status--'.e($submission->status).'">'.e($submission->statusLabel()).'</span>',
            e($submission->creator?->name ?? '—'),
            $submission->created_at?->format('d/m/Y H:i') ?? '—',
        ];

        foreach ($fields as $field) {
            $row[] = nl2br(e($values[$field->field_key] ?? '—'));
        }

        $row[] = $this->actions($user, $module, $matrix, $submission);

        return $row;
    }

    private function actions(User $user, string $module, Matrix $matrix, MatrixSubmission $submission): string
    {
        $showUrl = route('matrices.submissions.show', [
            'module' => $module,
            'matrix' => $matrix->id,
            'submission' => $submission->id,
        ]);

        $html = '<div class="matrices-row-actions">';
        $html .= '<a href="'.e($showUrl).'" class="matrices-row-actions__btn" title="Ver registro">Ver</a>';

        if (Gate::forUser($user)->allows('updateSubmission', [$matrix, $submission])) {
            $editUrl = route('matrices.submissions.edit', [
                'module' => $module,
                'matrix' => $matrix->id,
                'submission' => $submission->id,
            ]);

            $html .= '<a href="'.e($editUrl).'" class="matrices-row-actions__btn matrices-row-actions__btn--primary" title="Editar registro">Editar</a>';
        }

        // Archivar por fila: mismo permiso y confirmación que el detalle.
        if (Gate::forUser($user)->allows('archiveSubmission', [$matrix, $submission])) {
            $archiveUrl = route('matrices.submissions.archive', [
                'module' => $module,
                'matrix' => $matrix->id,
                'submission' => $submission->id,
            ]);

            $html .= '<form method="POST" action="'.e($archiveUrl).'" class="matrices-row-actions__form" '
                .'onsubmit="return confirm('.e(json_encode('¿Archivar el registro '.$submission->folio.'? Se conserva para consulta.', JSON_UNESCAPED_UNICODE)).');">'
                .csrf_field()
                .'<button type="submit" class="matrices-row-actions__btn matrices-row-actions__btn--danger" title="Archivar registro">Archivar</button>'
                .'</form>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param  Builder<MatrixSubmission>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('matrix_submissions.id');

            return;
        }

        $column = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($column) {
            0 => $query->orderBy('matrix_submissions.folio', $direction),
            1 => $query->orderBy('matrix_submissions.status', $direction),
            2 => $query->orderByRaw('(select name from users where users.id = matrix_submissions.created_by_user_id) '.$direction),
            3 => $query->orderBy('matrix_submissions.created_at', $direction),
            default => $query->orderByDesc('matrix_submissions.id'),
        };
    }
}
