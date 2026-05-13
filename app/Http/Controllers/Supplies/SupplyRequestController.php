<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\SupplyRequest;
use App\Models\SupplyProduct;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\Request;

class SupplyRequestController extends Controller
{
    use HasSupplyTabs;
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
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function show(string $module, SupplyRequest $supplyRequest)
    {
        $supplyRequest->load(['user', 'items.product', 'qualityReviewer']);

        return view('modules.supplies.show', [
            'module' => $module,
            'request' => $supplyRequest,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
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
            'subTabs' => $this->getSupplySubTabs($module),
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
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function qualityEdit(string $module, SupplyRequest $supplyRequest)
    {
        $supplyRequest->load(['user', 'items.product']);

        return view('modules.supplies.quality.edit', [
            'module' => $module,
            'request' => $supplyRequest,
            'subTabs' => $this->getSupplySubTabs($module),
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

    public function purchasingIndex(string $module)
    {
        $requests = SupplyRequest::whereIn('status', ['aprobada_calidad', 'en_compras', 'completada'])
            ->latest()
            ->with(['user', 'items.product'])
            ->get();

        return view('modules.supplies.purchasing.index', [
            'module' => $module,
            'requests' => $requests,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function purchasingEdit(string $module, SupplyRequest $supplyRequest)
    {
        $supplyRequest->load(['user', 'items.product', 'qualityReviewer']);

        // Al entrar a costear, marcamos como "en compras" si estaba solo aprobada
        if ($supplyRequest->status === 'aprobada_calidad') {
            $supplyRequest->update(['status' => 'en_compras']);
        }

        return view('modules.supplies.purchasing.edit', [
            'module' => $module,
            'request' => $supplyRequest,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function purchasingUpdate(Request $request, string $module, SupplyRequest $supplyRequest)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.purchasing_observations' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $supplyRequest) {
            $totalCost = 0;

            foreach ($request->items as $itemId => $data) {
                $item = $supplyRequest->items()->findOrFail($itemId);
                $item->update([
                    'unit_cost' => $data['unit_cost'],
                    'purchasing_observations' => $data['purchasing_observations'] ?? null,
                ]);

                $totalCost += ($item->approved_quantity * $data['unit_cost']);
            }

            $supplyRequest->update([
                'status' => 'completada',
                'purchasing_manager_id' => auth()->id(),
                'total_cost' => $totalCost,
            ]);
        });

        return redirect()->route('supplies.purchasing.index', ['module' => $module])
            ->with('success', 'Costos registrados y solicitud completada correctamente.');
    }
}
