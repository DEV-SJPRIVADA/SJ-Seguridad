<?php

namespace App\Http\Controllers\Matrices;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Matrices\Concerns\BuildsMatrixTabs;
use App\Http\Controllers\Matrices\Concerns\ResolvesMatrixContext;
use App\Http\Requests\Matrices\SaveMatrixRequest;
use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixField;
use App\Services\Matrices\MatrixAuditService;
use App\Services\Matrices\MatrixDefinitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MatrixBuilderController extends Controller
{
    use BuildsMatrixTabs, ResolvesMatrixContext;

    public function __construct(
        private readonly MatrixDefinitionService $definition,
        private readonly MatrixAuditService $audit,
    ) {}

    public function create(string $module): View
    {
        $user = $this->authorizeBoard($module);

        Gate::authorize('create', Matrix::class);

        return view('modules.matrices.builder', [
            'module' => $module,
            'matrix' => null,
            'fields' => [],
            'areaOptions' => config('access.areas', []),
            'defaultArea' => $user->area_key,
            'fieldTypes' => $this->fieldTypes(),
            'tabs' => $this->indexTabs($module, 'create'),
        ]);
    }

    public function store(string $module, SaveMatrixRequest $request): RedirectResponse
    {
        $this->authorizeBoard($module);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $matrix = $this->definition->create($user, $request->matrixData());
        $this->audit->matrix('create', 'matrix_created', $matrix);

        return redirect()
            ->route('matrices.edit', ['module' => $module, 'matrix' => $matrix->id])
            ->with('status', 'Matriz guardada como borrador. Revísala y publícala para compartirla.');
    }

    public function edit(string $module, Matrix $matrix): View
    {
        $user = $this->authorizeBoard($module);

        Gate::authorize('updateDefinition', $matrix);

        $matrix->load(['currentVersion', 'versions' => fn ($query) => $query->orderByDesc('version_number')]);

        $draft = $this->definition->latestDraftVersion($matrix);

        $fields = $draft !== null
            ? $draft->fields()->get()
            : ($matrix->currentVersion?->fields()->get() ?? collect());

        return view('modules.matrices.builder', [
            'module' => $module,
            'matrix' => $matrix,
            'fields' => $this->fieldPayload($fields),
            'areaOptions' => config('access.areas', []),
            'defaultArea' => $matrix->area_key ?? $user->area_key,
            'fieldTypes' => $this->fieldTypes(),
            'tabs' => $this->matrixTabs($matrix, $module, 'definition'),
            'hasDraft' => $draft !== null,
            'isPublished' => $matrix->isPublished(),
        ]);
    }

    public function update(string $module, Matrix $matrix, SaveMatrixRequest $request): RedirectResponse
    {
        $this->authorizeBoard($module);

        Gate::authorize('updateDefinition', $matrix);

        $this->definition->update($matrix, $request->matrixData());
        $this->audit->matrix('update', 'matrix_definition_updated', $matrix);

        $message = $matrix->isPublished()
            ? 'Cambios guardados en una versión nueva. Publícala para que los usuarios la vean.'
            : 'Borrador actualizado.';

        return redirect()
            ->route('matrices.edit', ['module' => $module, 'matrix' => $matrix->id])
            ->with('status', $message);
    }

    public function publish(string $module, Matrix $matrix): RedirectResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('updateDefinition', $matrix);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $version = $this->definition->publish($matrix, $user);
        $this->audit->matrix('publish', 'matrix_published', $matrix, [
            'version' => $version->version_number,
        ]);

        return redirect()
            ->route('matrices.share.index', ['module' => $module, 'matrix' => $matrix->id])
            ->with('status', 'Matriz publicada. Ahora compártela con las áreas o usuarios que la diligenciarán.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fieldPayload(Collection $fields): array
    {
        return $fields
            ->map(fn (MatrixField $field): array => [
                'field_key' => $field->field_key,
                'label' => $field->label,
                'type' => $field->type,
                'section' => $field->section,
                'help' => $field->help,
                'placeholder' => $field->placeholder,
                'required' => $field->required,
                'col_span' => $field->col_span,
                'show_in_table' => (bool) ($field->ruleSet()['show_in_table'] ?? false),
                'filterable' => (bool) ($field->ruleSet()['filterable'] ?? false),
                'preserve_case' => (bool) ($field->ruleSet()['preserve_case'] ?? false),
                'options' => $field->options ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function fieldTypes(): array
    {
        $types = [];

        foreach (MatrixDefinitionService::V1_TYPES as $type) {
            $types[$type] = MatrixField::labelForType($type);
        }

        return $types;
    }
}
