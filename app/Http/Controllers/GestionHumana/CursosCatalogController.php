<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\Cursos\StoreCursoEscuelaRequest;
use App\Http\Requests\GestionHumana\Cursos\StoreCursoTipoRequest;
use App\Http\Requests\GestionHumana\Cursos\UpdateCursoEscuelaRequest;
use App\Http\Requests\GestionHumana\Cursos\UpdateCursoTipoRequest;
use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Services\Access\CursosAccessService;
use App\Services\GestionHumana\CursosAuditLogService;
use App\Traits\HasCursosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CursosCatalogController extends Controller
{
    use HasCursosTabs;

    public function __construct(
        private readonly CursosAccessService $cursosAccess,
        private readonly CursosAuditLogService $auditLogService,
    ) {}

    public function index(): View
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        $tipos = CursoTipo::query()
            ->withCount('employeeCursos')
            ->ordered()
            ->get();

        $escuelas = CursoEscuela::query()
            ->ordered()
            ->get();

        return view('areas.gestion_humana.cursos.catalogo', [
            'subTabs' => $this->getCursosSubTabs('catalogo'),
            'tipos' => $tipos,
            'escuelas' => $escuelas,
        ]);
    }

    public function options(): JsonResponse
    {
        abort_unless($this->cursosAccess->canView(auth()->user()), 403);

        $options = CursoTipo::query()
            ->active()
            ->ordered()
            ->get(['id', 'tipo_curso'])
            ->map(fn (CursoTipo $tipo): array => [
                'value' => (string) $tipo->id,
                'label' => $tipo->tipo_curso,
            ])
            ->values();

        return response()->json(['data' => $options]);
    }

    public function store(StoreCursoTipoRequest $request): RedirectResponse
    {
        $tipo = CursoTipo::query()->create([
            'tipo_curso' => $request->string('tipo_curso')->toString(),
            'cargo_curso' => $request->input('cargo_curso'),
            'formato_para_cursos' => $request->input('formato_para_cursos'),
            'cursos' => $request->input('cursos'),
            'cargo_acredit' => $request->input('cargo_acredit'),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'curso_tipo',
            action: 'store',
            metadata: [
                'curso_tipo_id' => $tipo->id,
                'tipo_curso' => $tipo->tipo_curso,
            ],
            model: $tipo,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos'])
            ->with('status', 'Tipo de curso creado correctamente.');
    }

    public function update(UpdateCursoTipoRequest $request, CursoTipo $cursoTipo): RedirectResponse
    {
        $before = $cursoTipo->only([
            'tipo_curso',
            'cargo_curso',
            'formato_para_cursos',
            'cursos',
            'cargo_acredit',
            'is_active',
        ]);

        $cursoTipo->update([
            'tipo_curso' => $request->string('tipo_curso')->toString(),
            'cargo_curso' => $request->input('cargo_curso'),
            'formato_para_cursos' => $request->input('formato_para_cursos'),
            'cursos' => $request->input('cursos'),
            'cargo_acredit' => $request->input('cargo_acredit'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'curso_tipo',
            action: 'update',
            model: $cursoTipo,
            before: $before,
            after: $cursoTipo->only(array_keys($before)),
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos'])
            ->with('status', 'Tipo de curso actualizado correctamente.');
    }

    public function destroy(CursoTipo $cursoTipo): RedirectResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        if ($cursoTipo->employeeCursos()->exists()) {
            return redirect()
                ->route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos'])
                ->with('error', 'No se puede eliminar un tipo de curso que tiene registros asociados.');
        }

        $metadata = [
            'curso_tipo_id' => $cursoTipo->id,
            'tipo_curso' => $cursoTipo->tipo_curso,
        ];

        $cursoTipo->delete();

        $this->auditLogService->logEvent(
            eventType: 'curso_tipo',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos'])
            ->with('status', 'Tipo de curso eliminado.');
    }

    public function storeEscuela(StoreCursoEscuelaRequest $request): RedirectResponse
    {
        $escuela = CursoEscuela::query()->create([
            'codigo' => $request->string('codigo')->toString(),
            'nit' => $request->string('nit')->toString(),
            'nombre' => $request->string('nombre')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'curso_escuela',
            action: 'store',
            metadata: [
                'curso_escuela_id' => $escuela->id,
                'codigo' => $escuela->codigo,
                'nit' => $escuela->nit,
            ],
            model: $escuela,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.catalogo', ['catalog' => 'escuelas'])
            ->with('status', 'Escuela creada correctamente.');
    }

    public function updateEscuela(UpdateCursoEscuelaRequest $request, CursoEscuela $cursoEscuela): RedirectResponse
    {
        $before = $cursoEscuela->only(['codigo', 'nit', 'nombre', 'is_active']);

        $cursoEscuela->update([
            'codigo' => $request->string('codigo')->toString(),
            'nit' => $request->string('nit')->toString(),
            'nombre' => $request->string('nombre')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'curso_escuela',
            action: 'update',
            model: $cursoEscuela,
            before: $before,
            after: $cursoEscuela->only(array_keys($before)),
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.catalogo', ['catalog' => 'escuelas'])
            ->with('status', 'Escuela actualizada correctamente.');
    }

    public function destroyEscuela(CursoEscuela $cursoEscuela): RedirectResponse
    {
        abort_unless($this->cursosAccess->canEdit(auth()->user()), 403);

        $metadata = [
            'curso_escuela_id' => $cursoEscuela->id,
            'codigo' => $cursoEscuela->codigo,
            'nit' => $cursoEscuela->nit,
        ];

        $cursoEscuela->delete();

        $this->auditLogService->logEvent(
            eventType: 'curso_escuela',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.cursos.catalogo', ['catalog' => 'escuelas'])
            ->with('status', 'Escuela eliminada.');
    }
}
