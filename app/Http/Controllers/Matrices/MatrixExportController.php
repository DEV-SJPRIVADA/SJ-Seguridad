<?php

namespace App\Http\Controllers\Matrices;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Matrices\Concerns\ResolvesMatrixContext;
use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixField;
use App\Models\Matrices\MatrixSubmission;
use App\Services\Matrices\MatrixAccessResolver;
use App\Services\Matrices\MatrixAuditService;
use App\Services\Matrices\MatrixSubmissionFilterService;
use App\Services\Matrices\MatrixSubmissionPresenter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatrixExportController extends Controller
{
    use ResolvesMatrixContext;

    public function __construct(
        private readonly MatrixAccessResolver $accessResolver,
        private readonly MatrixSubmissionPresenter $presenter,
        private readonly MatrixSubmissionFilterService $filters,
        private readonly MatrixAuditService $audit,
    ) {}

    public function export(Request $request, string $module, Matrix $matrix): StreamedResponse
    {
        $user = $this->authorizeBoard($module);
        $this->authorizeMatrix('view', $matrix);

        $access = $this->accessResolver->resolve($user, $matrix);

        $query = MatrixSubmission::query()
            ->where('matrix_submissions.matrix_id', $matrix->id)
            ->whereNull('matrix_submissions.archived_at')
            ->visibleTo($access->canViewOthers, $user->id)
            ->with([
                'creator:id,name',
                'attachments',
                'values' => fn ($values) => $values->with('field:id,matrix_version_id,field_key,type,options'),
            ]);

        $this->filters->apply($query, $this->filters->fromRequest($request));

        $fields = $this->presenter->fieldsFor($matrix);

        $columns = [
            ['key' => 'folio', 'label' => 'Folio'],
            ['key' => fn (MatrixSubmission $submission) => $submission->statusLabel(), 'label' => 'Estado'],
            ['key' => fn (MatrixSubmission $submission) => $submission->creator?->name ?? '', 'label' => 'Registrado por'],
            ['key' => fn (MatrixSubmission $submission) => $submission->created_at?->format('d/m/Y H:i') ?? '', 'label' => 'Fecha de registro'],
        ];

        foreach ($fields as $field) {
            $columns[] = $this->columnForField($field);
        }

        $fileName = 'matriz-'.strtolower((string) $matrix->code).'-'.now()->format('Y-m-d').'.xlsx';

        $this->audit->matrix('export', 'matrix_exported', $matrix, [
            'filters' => $this->filters->fromRequest($request),
        ]);

        return BaseExport::downloadFromQuery(
            query: $query,
            columns: $columns,
            fileName: $fileName,
            title: $matrix->name,
            mapRow: fn (MatrixSubmission $submission): MatrixSubmission => $submission,
        );
    }

    /**
     * @return array{key: \Closure, label: string}
     */
    private function columnForField(MatrixField $field): array
    {
        if ($field->type === MatrixField::TYPE_FILE) {
            return [
                'key' => fn (MatrixSubmission $submission): int => $submission->attachments
                    ->where('field_id', $field->id)
                    ->count(),
                'label' => $field->label.' (adjuntos)',
            ];
        }

        if ($field->type === MatrixField::TYPE_SIGNATURE) {
            return [
                'key' => fn (MatrixSubmission $submission): string => $submission->attachments
                    ->where('field_id', $field->id)
                    ->isNotEmpty() ? 'Firmado' : 'Sin firma',
                'label' => $field->label,
            ];
        }

        return [
            'key' => fn (MatrixSubmission $submission): string => $this->presenter
                ->displayMap($submission)[$field->field_key] ?? '',
            'label' => $field->label,
        ];
    }
}
