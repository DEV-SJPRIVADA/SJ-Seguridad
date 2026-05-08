<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\SupplyProduct;
use App\Models\User;
use Illuminate\Http\Request;

class SupplyProductController extends Controller
{
    public function index(string $module)
    {
        $products = SupplyProduct::orderBy('category')->orderBy('name')->get();
        
        // Agrupamos por categoría para el formulario si es necesario, 
        // pero para la tabla mandamos la lista plana.
        
        return view('modules.supplies.products.index', [
            'module' => $module,
            'products' => $products,
            'subTabs' => (new User())->supplyBoardTabsFor($module),
        ]);
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
