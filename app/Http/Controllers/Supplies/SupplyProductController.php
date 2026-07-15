<?php

namespace App\Http\Controllers\Supplies;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Models\SupplyProduct;
use App\Models\User;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\Request;

class SupplyProductController extends Controller
{
    use HasSupplyTabs;

    public function index(string $module)
    {
        $products = SupplyProduct::orderBy('category')->orderBy('name')->get();
        
        return view('modules.supplies.products.index', [
            'module' => $module,
            'products' => $products,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function exportExcel(string $module): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $products = SupplyProduct::orderBy('category')->orderBy('name')->get();

        $columns = [
            ['key' => 'category', 'label' => 'Categoría'],
            ['key' => 'name', 'label' => 'Producto'],
            ['key' => 'description', 'label' => 'Descripción'],
            ['key' => fn($p) => $p->is_active ? 'Activo' : 'Inactivo', 'label' => 'Estado'],
        ];

        return (new BaseExport($products, $columns, 'catalogo_suministros_' . now()->format('Y-m-d') . '.xlsx', 'Catálogo de Suministros'))->download();
    }

    public function store(Request $request, string $module)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        SupplyProduct::create([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => true,
        ]);

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

        $product->update($request->all());

        return redirect()->route('supplies.products.index', ['module' => $module])
            ->with('success', 'Producto actualizado correctamente.');
    }
}
