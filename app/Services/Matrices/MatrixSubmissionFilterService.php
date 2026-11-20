<?php

namespace App\Services\Matrices;

use App\Models\Matrices\MatrixSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros compartidos por el listado y el export.
 */
final class MatrixSubmissionFilterService
{
    /**
     * @return array<string, mixed>
     */
    public function fromRequest(Request $request): array
    {
        return [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'assignee' => $request->string('assignee')->toString(),
            'fields' => $request->input('fields', []),
        ];
    }

    /**
     * @param  Builder<MatrixSubmission>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, int $userId): void
    {
        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%'.$search.'%';

            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('matrix_submissions.folio', 'like', $like)
                    ->orWhereHas('creator', fn (Builder $creator) => $creator->where('name', 'like', $like))
                    ->orWhereHas('assignee', fn (Builder $assignee) => $assignee->where('name', 'like', $like));
            });
        }

        $status = (string) ($filters['status'] ?? '');

        if (in_array($status, ['draft', 'submitted', 'archived'], true)) {
            $query->where('matrix_submissions.status', $status);
        }

        $from = (string) ($filters['from'] ?? '');

        if ($from !== '') {
            $query->whereDate('matrix_submissions.created_at', '>=', $from);
        }

        $to = (string) ($filters['to'] ?? '');

        if ($to !== '') {
            $query->whereDate('matrix_submissions.created_at', '<=', $to);
        }

        $assignee = (string) ($filters['assignee'] ?? '');

        if ($assignee === 'me') {
            $query->where('matrix_submissions.assigned_to_user_id', $userId);
        } elseif ($assignee === 'none') {
            $query->whereNull('matrix_submissions.assigned_to_user_id');
        } elseif ($assignee !== '' && ctype_digit($assignee)) {
            $query->where('matrix_submissions.assigned_to_user_id', (int) $assignee);
        }

        $fieldFilters = $filters['fields'] ?? [];

        if (is_array($fieldFilters)) {
            foreach ($fieldFilters as $fieldKey => $value) {
                if (! is_string($fieldKey) || $fieldKey === '' || ! is_scalar($value) || (string) $value === '') {
                    continue;
                }

                $query->whereHas('values', function (Builder $valueQuery) use ($fieldKey, $value): void {
                    $valueQuery
                        ->whereHas('field', fn (Builder $field) => $field->where('field_key', $fieldKey))
                        ->where(function (Builder $inner) use ($value): void {
                            $inner->where('value_text', (string) $value)
                                ->orWhere('value_json', 'like', '%"'.$this->escapeLike((string) $value).'"%');
                        });
                });
            }
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
