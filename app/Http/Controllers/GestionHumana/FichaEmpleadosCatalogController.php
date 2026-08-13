<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\StorePayrollCatalogItemRequest;
use App\Http\Requests\GestionHumana\UpdatePayrollCatalogItemRequest;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FichaEmpleadosCatalogController extends Controller
{
    use HasFichaEmpleadosTabs;

    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly EmployeeFichaCatalogService $catalogService,
    ) {}

    public function index(): View
    {
        $this->authorizeManage();

        return view('areas.gestion_humana.ficha-empleados.catalogs.index', [
            'catalogs' => $this->catalogService->catalogsForAdmin(),
            'terminationLetterTemplates' => TerminationLetterDocumentTemplate::query()->ordered()->get()->groupBy('termination_cause_code'),
            'terminationLetterPlaceholders' => config('employee_ficha.termination_letter_placeholders', []),
            'subTabs' => $this->getFichaEmpleadosSubTabs('catalogos'),
        ]);
    }

    public function store(StorePayrollCatalogItemRequest $request, string $type): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless($this->catalogService->isValidType($type), 404);

        PayrollCatalogItem::query()->create([
            'catalog_type' => $type,
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($request->input('sort_order') ?? 0),
        ]);

        return redirect()
            ->route('gestion-humana.ficha-empleados.catalogs.index')
            ->with('status', 'Catalogo actualizado correctamente.')
            ->withFragment('section-'.$type);
    }

    public function update(UpdatePayrollCatalogItemRequest $request, string $type, PayrollCatalogItem $item): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless($this->catalogService->isValidType($type), 404);
        abort_unless($item->catalog_type === $type, 404);

        $item->update([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->input('sort_order') ?? $item->sort_order ?? 0),
        ]);

        return redirect()
            ->route('gestion-humana.ficha-empleados.catalogs.index')
            ->with('status', 'Registro de catalogo actualizado.')
            ->withFragment('section-'.$type);
    }

    public function destroy(string $type, PayrollCatalogItem $item): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless($this->catalogService->isValidType($type), 404);
        abort_unless($item->catalog_type === $type, 404);

        $item->delete();

        return redirect()
            ->route('gestion-humana.ficha-empleados.catalogs.index')
            ->with('status', 'Registro eliminado del catalogo.')
            ->withFragment('section-'.$type);
    }

    private function authorizeManage(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canManage(auth()->user()), 403);
    }
}
