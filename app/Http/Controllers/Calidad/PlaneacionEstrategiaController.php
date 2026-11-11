<?php

namespace App\Http\Controllers\Calidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calidad\Planeacion\StorePlaneacionDofaRequest;
use App\Http\Requests\Calidad\Planeacion\StorePlaneacionObjectiveRequest;
use App\Http\Requests\Calidad\Planeacion\StorePlaneacionStrategyRequest;
use App\Models\QualityPlanning\QualityPlanningDofa;
use App\Models\QualityPlanning\QualityPlanningDofaItem;
use App\Models\QualityPlanning\QualityPlanningObjective;
use App\Models\QualityPlanning\QualityPlanningStrategy;
use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use App\Services\Calidad\Planeacion\PlaneacionEstrategiaService;
use App\Traits\HasPlaneacionTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pestaña Estrategia: DOFA, objetivos y estrategias encadenados (FEAT-051).
 */
class PlaneacionEstrategiaController extends Controller
{
    use HasPlaneacionTabs;

    public function __construct(
        private readonly PlaneacionEstrategicaAccessService $access,
        private readonly PlaneacionEstrategiaService $estrategia,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $status = (string) $request->string('status')->toString();

        $query = QualityPlanningDofa::query()
            ->withCount(['objectives', 'items'])
            ->orderByDesc('starts_on')
            ->orderByDesc('id');

        if ($status === 'archived') {
            $query->whereNotNull('archived_at');
        } elseif ($status !== '') {
            $query->whereNull('archived_at')->where('status', $status);
        } else {
            // Por defecto la lista oculta los archivados; se ven con el filtro Archivados.
            $query->whereNull('archived_at');
        }

        return view('areas.calidad.planeacion.estrategia.index', [
            'subTabs' => $this->getPlaneacionSubTabs('estrategia'),
            'dofas' => $query->get(),
            'status' => $status,
            'statusOptions' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => QualityPlanningDofa::STATUS_DRAFT, 'label' => 'Borrador'],
                ['value' => QualityPlanningDofa::STATUS_ACTIVE, 'label' => 'Vigente'],
                ['value' => 'archived', 'label' => 'Archivados'],
            ],
            'canEdit' => $this->access->canEditEstrategia($this->actor()),
        ]);
    }

    public function storeDofa(StorePlaneacionDofaRequest $request): RedirectResponse
    {
        $dofa = $this->estrategia->createDofa(
            $request->user(),
            $request->validated(),
            $request->quadrants(),
        );

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $dofa)
            ->with('status', 'DOFA creado. Agregue objetivos y estrategias.');
    }

    public function show(QualityPlanningDofa $dofa): View
    {
        $this->authorizeView();

        $dofa->load([
            'items',
            'objectives.strategies',
        ]);

        return view('areas.calidad.planeacion.estrategia.show', [
            'subTabs' => $this->getPlaneacionSubTabs('estrategia'),
            'dofa' => $dofa,
            'itemsByQuadrant' => $dofa->itemsByQuadrant(),
            'quadrants' => QualityPlanningDofaItem::QUADRANTS,
            'canEdit' => $this->access->canEditEstrategia($this->actor()) && ! $dofa->isArchived(),
        ]);
    }

    public function updateDofa(StorePlaneacionDofaRequest $request, QualityPlanningDofa $dofa): RedirectResponse
    {
        abort_if($dofa->isArchived(), 403);

        $this->estrategia->updateDofa($request->user(), $dofa, $request->validated(), $request->quadrants());

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $dofa)
            ->with('status', 'DOFA actualizado.');
    }

    public function activateDofa(QualityPlanningDofa $dofa): RedirectResponse
    {
        $this->authorizeEdit();
        abort_if($dofa->isArchived(), 403);

        $this->estrategia->activateDofa($this->actor(), $dofa);

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $dofa)
            ->with('status', 'DOFA marcado como vigente.');
    }

    public function archiveDofa(QualityPlanningDofa $dofa): RedirectResponse
    {
        $this->authorizeEdit();
        abort_if($dofa->isArchived(), 403);

        $this->estrategia->archiveDofa($this->actor(), $dofa);

        return redirect()
            ->route('calidad.planeacion.estrategia')
            ->with('status', 'DOFA archivado. Sus objetivos y estrategias se conservan.');
    }

    public function storeObjective(StorePlaneacionObjectiveRequest $request, QualityPlanningDofa $dofa): RedirectResponse
    {
        abort_if($dofa->isArchived(), 403);

        $this->estrategia->createObjective($request->user(), $dofa, $request->validated());

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $dofa)
            ->with('status', 'Objetivo agregado.');
    }

    public function updateObjective(StorePlaneacionObjectiveRequest $request, QualityPlanningObjective $objective): RedirectResponse
    {
        abort_if($objective->dofa?->isArchived(), 403);

        $this->estrategia->updateObjective($request->user(), $objective, $request->validated());

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $objective->dofa_id)
            ->with('status', 'Objetivo actualizado.');
    }

    public function destroyObjective(QualityPlanningObjective $objective): RedirectResponse
    {
        $this->authorizeEdit();
        abort_if($objective->dofa?->isArchived(), 403);

        $dofaId = (int) $objective->dofa_id;
        $this->estrategia->deleteObjective($this->actor(), $objective);

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $dofaId)
            ->with('status', 'Objetivo eliminado junto con sus estrategias.');
    }

    public function storeStrategy(StorePlaneacionStrategyRequest $request, QualityPlanningObjective $objective): RedirectResponse
    {
        abort_if($objective->dofa?->isArchived(), 403);

        $this->estrategia->createStrategy($request->user(), $objective, $request->validated());

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $objective->dofa_id)
            ->with('status', 'Estrategia agregada.');
    }

    public function updateStrategy(StorePlaneacionStrategyRequest $request, QualityPlanningStrategy $strategy): RedirectResponse
    {
        abort_if($strategy->objective?->dofa?->isArchived(), 403);

        $this->estrategia->updateStrategy($request->user(), $strategy, $request->validated());

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $strategy->objective?->dofa_id)
            ->with('status', 'Estrategia actualizada.');
    }

    public function destroyStrategy(QualityPlanningStrategy $strategy): RedirectResponse
    {
        $this->authorizeEdit();
        abort_if($strategy->objective?->dofa?->isArchived(), 403);

        $dofaId = (int) $strategy->objective?->dofa_id;
        $this->estrategia->deleteStrategy($this->actor(), $strategy);

        return redirect()
            ->route('calidad.planeacion.estrategia.show', $dofaId)
            ->with('status', 'Estrategia eliminada.');
    }

    private function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function authorizeView(): void
    {
        abort_unless($this->access->canViewEstrategia($this->actor()), 403);
    }

    private function authorizeEdit(): void
    {
        abort_unless($this->access->canEditEstrategia($this->actor()), 403);
    }
}
