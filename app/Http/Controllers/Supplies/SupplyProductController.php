<?php

namespace App\Http\Controllers\Supplies;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Models\SupplyProduct;
use App\Services\Supplies\SupplyAuditLogService;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplyProductController extends Controller
{
    use HasSupplyTabs;

    public function __construct(
        private readonly SupplyAuditLogService $auditLogService,
    ) {}

    public function index(string $module)
    {
        $products = SupplyProduct::orderBy('category')->orderBy('name')->get();

        return view('modules.supplies.products.index', [
            'module' => $module,
            'products' => $products,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function exportExcel(string $module): StreamedResponse
    {
        $products = SupplyProduct::orderBy('category')->orderBy('name')->get();

        $columns = [
            ['key' => 'category', 'label' => 'Categoría'],
            ['key' => 'name', 'label' => 'Producto'],
            ['key' => 'description', 'label' => 'Descripción'],
            ['key' => fn ($p) => $p->is_active ? 'Activo' : 'Inactivo', 'label' => 'Estado'],
        ];

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'catalog_excel',
            metadata: [
                'row_count' => $products->count(),
            ],
            area: $module,
        );

        return (new BaseExport($products, $columns, 'catalogo_suministros_'.now()->format('Y-m-d').'.xlsx', 'Catálogo de Suministros'))->download();
    }

    public function store(Request $request, string $module)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        $product = SupplyProduct::create([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => true,
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'supply_product',
            action: 'create',
            model: $product,
            before: null,
            after: [
                'name' => $product->name,
                'category' => $product->category,
            ],
            area: $module,
        );

        return redirect()->route('supplies.products.index', ['module' => $module])
            ->with('success', 'Producto agregado al catálogo correctamente.');
    }

    public function update(Request $request, string $module, SupplyProduct $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        $before = [
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
        ];
        $previousIsActive = (bool) $product->is_active;

        $product->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $product->refresh();

        $after = [
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
        ];

        $oldValues = [];
        $newValues = [];

        foreach ($before as $field => $beforeValue) {
            $afterValue = $after[$field] ?? null;

            if ($beforeValue !== $afterValue) {
                $oldValues[$field] = $beforeValue;
                $newValues[$field] = $afterValue;
            }
        }

        if ($oldValues !== []) {
            $this->auditLogService->logModelChange(
                eventType: 'supply_product',
                action: 'update',
                model: $product,
                before: $oldValues,
                after: $newValues,
                area: $module,
            );
        }

        $currentIsActive = (bool) $product->is_active;

        if ($previousIsActive !== $currentIsActive) {
            $this->auditLogService->logEvent(
                eventType: 'supply_product',
                action: $currentIsActive ? 'activate' : 'deactivate',
                model: $product,
                metadata: ['previous_is_active' => $previousIsActive],
                area: $module,
            );
        }

        return redirect()->route('supplies.products.index', ['module' => $module])
            ->with('success', 'Producto actualizado correctamente.');
    }
}
