<?php

namespace App\Http\Controllers\Matrices;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Matrices\Concerns\BuildsMatrixTabs;
use App\Http\Controllers\Matrices\Concerns\ResolvesMatrixContext;
use App\Http\Requests\Matrices\StoreMatrixSubmissionRequest;
use App\Http\Requests\Matrices\UpdateMatrixSubmissionRequest;
use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixSubmission;
use App\Services\Matrices\MatrixAccessResolver;
use App\Services\Matrices\MatrixAuditService;
use App\Services\Matrices\MatrixSubmissionDatatableService;
use App\Services\Matrices\MatrixSubmissionFilterService;
use App\Services\Matrices\MatrixSubmissionPresenter;
use App\Services\Matrices\MatrixSubmissionService;
use App\Services\Users\UserSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MatrixSubmissionController extends Controller
{
    use BuildsMatrixTabs, ResolvesMatrixContext;

    public function __construct(
        private readonly MatrixSubmissionPresenter $presenter,
        private readonly MatrixSubmissionService $submissions,
        private readonly MatrixSubmissionDatatableService $datatable,
        private readonly MatrixSubmissionFilterService $filters,
        private readonly MatrixAccessResolver $accessResolver,
        private readonly MatrixAuditService $audit,
    ) {}

    public function index(Request $request, string $module, Matrix $matrix): View
    {
        $user = $this->authorizeBoard($module);
        $this->authorizeMatrix('view', $matrix);

        $access = $this->accessResolver->resolve($user, $matrix);
        $filters = $this->filters->fromRequest($request);
        $query = $this->activeFilterQuery($filters);

        return view('modules.matrices.submissions.index', [
            'module' => $module,
            'matrix' => $matrix,
            'tabs' => $this->matrixTabs($matrix, $module, 'records'),
            'tableFields' => $this->presenter->tableFields($matrix),
            'filterFields' => $this->presenter->filterFields($matrix),
            'filters' => $filters,
            'hasActiveFilters' => $query !== [],
            'canRegister' => $access->canRegister && ! $matrix->isArchived(),
            'datatableUrl' => route('matrices.submissions.datatable', ['module' => $module, 'matrix' => $matrix->id, ...$query]),
            'exportUrl' => route('matrices.export', ['module' => $module, 'matrix' => $matrix->id, ...$query]),
        ]);
    }

    public function datatable(Request $request, string $module, Matrix $matrix): JsonResponse
    {
        $user = $this->authorizeBoard($module);
        $this->authorizeMatrix('view', $matrix);

        return $this->datatable->respond($request, $user, $matrix, $module);
    }

    public function create(string $module, Matrix $matrix): View
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('registerSubmission', $matrix);

        $fields = $this->presenter->fieldsFor($matrix);

        abort_if($fields->isEmpty(), 403, 'La matriz todavía no tiene una versión publicada.');

        return view('modules.matrices.submissions.form', [
            'module' => $module,
            'matrix' => $matrix,
            'submission' => null,
            'fields' => $fields,
            // Colección vacía: la vista resuelve valores con `->get(field_id)`.
            'values' => collect(),
            'attachments' => collect(),
            'profileSignatureUri' => app(UserSignatureService::class)->dataUri(auth()->user()),
            'tabs' => $this->matrixTabs($matrix, $module, 'records'),
        ]);
    }

    public function store(string $module, Matrix $matrix, StoreMatrixSubmissionRequest $request): RedirectResponse
    {
        $this->authorizeBoard($module);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $submission = $this->submissions->create($user, $matrix, $this->payload($request));
        $this->audit->submission('create', 'matrix_submission_created', $submission);

        // Al guardar se vuelve al listado de registros, no al detalle.
        return redirect()
            ->route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id])
            ->with('status', 'Registro '.$submission->folio.' guardado.');
    }

    public function show(string $module, Matrix $matrix, MatrixSubmission $submission): View
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('viewSubmission', $matrix, $submission);

        $submission->load([
            'version',
            'creator:id,name',
            'values.field',
            'attachments.field',
        ]);

        $fields = $this->presenter->fieldsForSubmission($submission);

        return view('modules.matrices.submissions.show', [
            'module' => $module,
            'matrix' => $matrix,
            'submission' => $submission,
            'fields' => $fields,
            'values' => $submission->values->keyBy('field_id'),
            'attachments' => $submission->attachments->groupBy('field_id'),
            'canEdit' => Gate::allows('updateSubmission', [$matrix, $submission]),
            'canArchive' => Gate::allows('archiveSubmission', [$matrix, $submission]),
            'tabs' => $this->matrixTabs($matrix, $module, 'records'),
        ]);
    }

    public function edit(string $module, Matrix $matrix, MatrixSubmission $submission): View
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('updateSubmission', $matrix, $submission);

        $submission->load(['version', 'values.field', 'attachments.field']);

        $fields = $this->presenter->fieldsForSubmission($submission);

        return view('modules.matrices.submissions.form', [
            'module' => $module,
            'matrix' => $matrix,
            'submission' => $submission,
            'fields' => $fields,
            'values' => $submission->values->keyBy('field_id'),
            'attachments' => $submission->attachments->groupBy('field_id'),
            'profileSignatureUri' => app(UserSignatureService::class)->dataUri(auth()->user()),
            'tabs' => $this->matrixTabs($matrix, $module, 'records'),
        ]);
    }

    public function update(
        string $module,
        Matrix $matrix,
        MatrixSubmission $submission,
        UpdateMatrixSubmissionRequest $request,
    ): RedirectResponse {
        $this->authorizeBoard($module);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $this->submissions->update($submission, $this->payload($request), $user);
        $this->audit->submission('update', 'matrix_submission_updated', $submission);

        // Igual que en el alta: se regresa al listado de registros.
        return redirect()
            ->route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id])
            ->with('status', 'Registro '.$submission->folio.' actualizado.');
    }

    public function archive(string $module, Matrix $matrix, MatrixSubmission $submission): RedirectResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('archiveSubmission', $matrix, $submission);

        $this->submissions->archive($submission);
        $this->audit->submission('archive', 'matrix_submission_archived', $submission);

        return redirect()
            ->route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id])
            ->with('status', 'Registro archivado. Se conserva para consulta histórica.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        return [
            'values' => (array) $request->input('values', []),
            'files' => (array) $request->file('files', []),
            'signature_mode' => (array) $request->input('signature_mode', []),
            'signature_canvas' => (array) $request->input('signature_canvas', []),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function activeFilterQuery(array $filters): array
    {
        $query = [];

        foreach (['q', 'status', 'from', 'to'] as $key) {
            $value = $filters[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $query[$key] = $value;
            }
        }

        if (! empty($filters['fields']) && is_array($filters['fields'])) {
            $query['fields'] = $filters['fields'];
        }

        return $query;
    }
}
