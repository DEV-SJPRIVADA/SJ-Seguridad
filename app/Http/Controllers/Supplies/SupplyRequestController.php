<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\SupplyRequest;
use App\Models\SupplyProduct;
use Illuminate\Http\Request;

class SupplyRequestController extends Controller
{
    public function index(string $module)
    {
        $requests = SupplyRequest::where('user_id', auth()->id())
            ->where('area_key', $module)
            ->latest()
            ->with(['items.product'])
            ->get();

        return view('modules.supplies.index', [
            'module' => $module,
            'requests' => $requests,
            'subTabs' => $this->getSubTabs($module),
        ]);
    }

    public function show(string $module, SupplyRequest $supplyRequest)
    {
        $supplyRequest->load(['user', 'items.product', 'qualityReviewer']);

        return view('modules.supplies.show', [
            'module' => $module,
            'request' => $supplyRequest,
            'subTabs' => $this->getSubTabs($module),
        ]);
    }

    private function getSubTabs(string $module)
    {
        $user = auth()->user();
        $tabs = $user->supplyBoardTabsFor($module);
        $routeName = request()->route()?->getName();
        
        return $tabs->map(function($tab) use ($module, $routeName) {
            $targetRoute = match($tab) {
                'mis_solicitudes' => 'supplies.index',
                'revision_calidad' => 'supplies.quality.index',
                'gestion_compras' => 'supplies.purchasing.index',
                'catalogo' => 'supplies.products.index',
                default => 'supplies.index',
            };

            return [
                'label' => config("access.supply_tabs.{$tab}"),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $routeName === $targetRoute,
            ];
        });
    }

    public function create(string $module)
    {
        $products = SupplyProduct::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return view('modules.supplies.create', [
            'module' => $module,
            'products' => $products,
            'subTabs' => $this->getSubTabs($module),
        ]);
    }

    public function store(Request $request, string $module)
    {
        $request->validate([
            'observations' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:supply_products,id',
            'items.*.current_inventory' => 'required|integer|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $module) {
            $supplyRequest = SupplyRequest::create([
                'user_id' => auth()->id(),
                'area_key' => $module,
                'status' => 'pendiente_calidad',
                'observations' => $request->observations,
            ]);

            foreach ($request->items as $item) {
                $supplyRequest->items()->create([
                    'supply_product_id' => $item['product_id'],
                    'current_inventory' => $item['current_inventory'],
                    'requested_quantity' => $item['quantity'],
                ]);
            }
        });

        return redirect()->route('supplies.index', ['module' => $module])
            ->with('success', 'Solicitud enviada correctamente a Calidad.');
    }

    public function qualityIndex(string $module)
    {
        $requests = SupplyRequest::latest()
            ->with(['user', 'items.product'])
            ->get();

        return view('modules.supplies.quality.index', [
            'module' => $module,
            'requests' => $requests,
            'subTabs' => $this->getSubTabs($module),
        ]);
    }

    public function qualityEdit(string $module, SupplyRequest $supplyRequest)
    {
        $supplyRequest->load(['user', 'items.product']);

        return view('modules.supplies.quality.edit', [
            'module' => $module,
            'request' => $supplyRequest,
            'subTabs' => $this->getSubTabs($module),
        ]);
    }

    public function qualityUpdate(Request $request, string $module, SupplyRequest $supplyRequest)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'quality_observations' => 'nullable|string',
            'items' => 'required_if:action,approve|array',
            'items.*.approved_quantity' => 'required_if:action,approve|integer|min:0',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $supplyRequest) {
            $isApprove = $request->action === 'approve';
            
            $supplyRequest->update([
                'status' => $isApprove ? 'aprobada_calidad' : 'rechazada_calidad',
                'quality_reviewer_id' => auth()->id(),
                'quality_observations' => $request->quality_observations,
            ]);

            if ($isApprove) {
                foreach ($request->items as $itemId => $data) {
                    $supplyRequest->items()->where('id', $itemId)->update([
                        'approved_quantity' => $data['approved_quantity'],
                    ]);
                }
            }
        });

        return redirect()->route('supplies.quality.index', ['module' => $module])
            ->with('success', 'Solicitud procesada correctamente.');
    }

    public function purchasingIndex()
    {
        // Bandeja para Compras
    }

    public function purchasingEdit(string $module, SupplyRequest $supplyRequest)
    {
        // Formulario de costeo para Compras
    }

    public function purchasingUpdate(Request $request, string $module, SupplyRequest $supplyRequest)
    {
        // Procesar costeo y cierre por Compras
    }
}
