<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\SupplyProduct;
use Illuminate\Http\Request;

class SupplyProductController extends Controller
{
    public function index()
    {
        // CRUD de catálogo
    }

    public function store(Request $request)
    {
        // Guardar nuevo producto
    }

    public function update(Request $request, SupplyProduct $product)
    {
        // Actualizar producto
    }
}
