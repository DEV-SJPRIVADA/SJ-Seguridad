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
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function index(string $module, Request $request): View|RedirectResponse
    {
        $user = $this->authorizeBoard($module);

        // Quien no administra matrices no ve tarjetas: entra directo a la primera
        // matriz que tenga como pestaña.
        if (! $this->listing->canSeeAdminCards($user)) {
            $first = $this->listing->tabbedMatrices($user)->first();
            abort_if($first === null, 403);

            return redirect()->route('matrices.submissions.index', [
                'module' => $module,
                'matrix' => $first->id,
            ]);
        }

        $statusOptions = [
            Matrix::STATUS_DRAFT => 'Borrador',
            Matrix::STATUS_PUBLISHED => 'Publicada',
        ];

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'area' => trim((string) $request->query('area', '')),
            'owner' => trim((string) $request->query('owner', '')),
            'status' => (string) $request->query('status', ''),
        ];

        if (! array_key_exists($filters['status'], $statusOptions)) {
            $filters['status'] = '';
        }

        $available = $this->listing->visibleMatrices($user);

        $rows = $this->listing->visibleMatrices($user, $filters)
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
            'total' => $available->count(),
            'filters' => $filters,
            'areaOptions' => $this->areaFilterOptions($available),
            'ownerOptions' => $this->ownerFilterOptions($available),
            'statusOptions' => $statusOptions,
            'hasActiveFilters' => collect($filters)->contains(fn (string $value): bool => $value !== ''),
            'canCreate' => $user->can('matrices.manage'),
            'tabs' => $this->indexTabs($module),
        ]);
    }

    /**
     * Lista de las matrices del usuario (propias y compartidas) con buscador por
     * nombre o código y filtros por área, creador y estado. Reemplaza el grid de
     * tarjetas para quien no administra matrices.
     */
    public function mine(string $module, Request $request): View
    {
        $user = $this->authorizeBoard($module);

        $statusOptions = [
            Matrix::STATUS_DRAFT => 'Borrador',
            Matrix::STATUS_PUBLISHED => 'Publicada',
        ];

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'area' => trim((string) $request->query('area', '')),
            'owner' => trim((string) $request->query('owner', '')),
            'status' => (string) $request->query('status', ''),
        ];

        if (! array_key_exists($filters['status'], $statusOptions)) {
            $filters['status'] = '';
        }

        $available = $this->listing->mineMatrices($user);

        $rows = $this->listing->mineMatrices($user, $filters)
            ->map(fn (Matrix $matrix): array => [
                'matrix' => $matrix,
                'access' => $this->accessResolver->resolve($user, $matrix),
                'is_owner' => (int) $matrix->owner_user_id === (int) $user->id,
            ])
            ->values();

        return view('modules.matrices.mine', [
            'module' => $module,
            'rows' => $rows,
            'total' => $available->count(),
            'filters' => $filters,
            'areaOptions' => $this->areaFilterOptions($available),
            'ownerOptions' => $this->ownerFilterOptions($available),
            'statusOptions' => $statusOptions,
            'hasActiveFilters' => collect($filters)->contains(fn (string $value): bool => $value !== ''),
            'canCreate' => $this->listing->canSeeAdminCards($user),
            'tabs' => $this->indexTabs($module, 'mine'),
        ]);
    }

    /**
     * Áreas presentes en las matrices del usuario (no todo el catálogo).
     *
     * @param  Collection<int, Matrix>  $matrices
     * @return array<int, array{value: string, label: string}>
     */
    private function areaFilterOptions(Collection $matrices): array
    {
        $areas = config('access.areas', []);

        return $matrices
            ->map(fn (Matrix $matrix): array => [
                'value' => (string) $matrix->area_key,
                'label' => (string) ($areas[$matrix->area_key] ?? 'Sin área'),
            ])
            ->filter(fn (array $option): bool => $option['value'] !== '')
            ->unique('value')
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * Creadores presentes en las matrices del usuario.
     *
     * @param  Collection<int, Matrix>  $matrices
     * @return array<int, array{value: string, label: string}>
     */
    private function ownerFilterOptions(Collection $matrices): array
    {
        return $matrices
            ->filter(fn (Matrix $matrix): bool => $matrix->owner !== null)
            ->map(fn (Matrix $matrix): array => [
                'value' => (string) $matrix->owner->id,
                'label' => (string) $matrix->owner->name,
            ])
            ->unique('value')
            ->sortBy('label')
            ->values()
            ->all();
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
