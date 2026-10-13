<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Models\GhAreaDashboardConcepto;
use App\Models\GhAreaDashboardConceptoCargo;
use App\Services\GestionHumana\GestionHumanaAreaDashboardService;
use App\Services\GestionHumana\GhAreaDashboardConceptoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard de área Gestión Humana (informe mensual KPI + gráficos + conceptos).
 */
class GestionHumanaAreaDashboardController extends Controller
{
    public function __construct(
        private readonly GestionHumanaAreaDashboardService $dashboardService,
        private readonly GhAreaDashboardConceptoService $conceptoService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeDashboard();

        $filters = $this->filtersFromRequest($request);
        $payload = $this->dashboardService->metrics($filters);
        $options = $this->dashboardService->filterSelectOptions((int) $payload['filters']['anio']);

        return view('areas.gestion_humana.dashboard', [
            'filters' => $payload['filters'],
            'initialPayload' => $payload,
            'metricsUrl' => route('gestion-humana.dashboard.metrics'),
            'conceptosUrl' => route('gestion-humana.dashboard.conceptos.index'),
            'conceptosStoreUrl' => route('gestion-humana.dashboard.conceptos.store'),
            'yearOptions' => $options['anios'],
            'monthOptions' => $options['meses'],
            'cityOptions' => $options['ciudades'],
            'positionOptions' => $options['cargos'],
            'clientOptions' => $options['clientes'],
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $this->authorizeDashboard();

        return response()->json(
            $this->dashboardService->metrics($this->filtersFromRequest($request))
        );
    }

    public function conceptosIndex(): JsonResponse
    {
        $this->authorizeDashboard();

        return response()->json($this->conceptoService->catalog());
    }

    public function conceptosStore(Request $request): JsonResponse
    {
        $this->authorizeDashboard();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $concepto = $this->conceptoService->createConcepto($validated['name']);

        return response()->json([
            'concepto' => [
                'id' => $concepto->id,
                'name' => $concepto->name,
                'sort_order' => $concepto->sort_order,
            ],
            'catalog' => $this->conceptoService->catalog(),
        ], 201);
    }

    public function conceptosUpdate(Request $request, GhAreaDashboardConcepto $concepto): JsonResponse
    {
        $this->authorizeDashboard();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $updated = $this->conceptoService->updateConcepto($concepto, $validated['name']);

        return response()->json([
            'concepto' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'sort_order' => $updated->sort_order,
            ],
            'catalog' => $this->conceptoService->catalog(),
        ]);
    }

    public function conceptosDestroy(GhAreaDashboardConcepto $concepto): JsonResponse
    {
        $this->authorizeDashboard();

        $this->conceptoService->deleteConcepto($concepto);

        return response()->json([
            'catalog' => $this->conceptoService->catalog(),
        ]);
    }

    public function cargosStore(Request $request, GhAreaDashboardConcepto $concepto): JsonResponse
    {
        $this->authorizeDashboard();

        $validated = $request->validate([
            'position_name' => ['required', 'string', 'max:255'],
        ]);

        $cargo = $this->conceptoService->assignCargo($concepto, $validated['position_name']);

        return response()->json([
            'cargo' => [
                'id' => $cargo->id,
                'position_key' => $cargo->position_key,
                'position_label' => $cargo->position_label,
                'concepto_id' => $cargo->concepto_id,
            ],
            'catalog' => $this->conceptoService->catalog(),
        ], 201);
    }

    public function cargosDestroy(GhAreaDashboardConceptoCargo $cargo): JsonResponse
    {
        $this->authorizeDashboard();

        $this->conceptoService->unassignCargo($cargo);

        return response()->json([
            'catalog' => $this->conceptoService->catalog(),
        ]);
    }

    /**
     * @return array{anio: string, mes: string, ciudad: string, cargo: string, cliente: string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'anio' => trim((string) $request->input('anio', '')),
            'mes' => trim((string) $request->input('mes', '')),
            'ciudad' => trim((string) $request->input('ciudad', '')),
            'cargo' => trim((string) $request->input('cargo', '')),
            'cliente' => trim((string) $request->input('cliente', '')),
        ];
    }

    private function authorizeDashboard(): void
    {
        abort_unless(
            auth()->user()?->can('view.board.gestion_humana.dashboard')
            || auth()->user()?->can('manage.users'),
            403
        );
    }
}
