<?php

namespace App\Http\Controllers\Matrices;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Matrices\Concerns\BuildsMatrixTabs;
use App\Http\Controllers\Matrices\Concerns\ResolvesMatrixContext;
use App\Models\Matrices\Matrix;
use App\Services\Matrices\MatrixAccessResolver;
use App\Services\Matrices\MatrixAuditService;
use App\Services\Matrices\MatrixDefinitionService;
use App\Services\Matrices\MatrixListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatrixController extends Controller
{
    use BuildsMatrixTabs, ResolvesMatrixContext;

    public function __construct(
        private readonly MatrixListingService $listing,
        private readonly MatrixAccessResolver $accessResolver,
        private readonly MatrixDefinitionService $definition,
        private readonly MatrixAuditService $audit,
    ) {}

    public function index(string $module): View
    {
        $user = $this->authorizeBoard($module);

        $rows = $this->listing->visibleMatrices($user)
            ->map(function (Matrix $matrix) use ($user): array {
                $access = $this->accessResolver->resolve($user, $matrix);

                return [
                    'matrix' => $matrix,
                    'access' => $access,
                    'is_owner' => (int) $matrix->owner_user_id === (int) $user->id,
                    'can_manage' => $access->canManageDefinition,
                    'records_count' => (int) ($matrix->submissions_count ?? 0),
                ];
            })
            ->values();

        return view('modules.matrices.index', [
            'module' => $module,
            'rows' => $rows,
            'canCreate' => $user->can('matrices.manage'),
            'tabs' => $this->indexTabs($module),
        ]);
    }

    public function archive(string $module, Matrix $matrix): RedirectResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('archive', $matrix);

        $this->definition->archive($matrix);
        $this->audit->matrix('archive', 'matrix_archived', $matrix);

        return redirect()
            ->route('matrices.index', ['module' => $module])
            ->with('status', 'Matriz archivada. Sus registros se conservan.');
    }
}
