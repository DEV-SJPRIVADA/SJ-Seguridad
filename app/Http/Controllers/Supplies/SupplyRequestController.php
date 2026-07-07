<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Mail\SupplyRequestNotification;
use App\Models\SupplyRequest;
use App\Models\SupplyProduct;
use App\Models\User;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $this->authorizeSupplyView($supplyRequest);

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

        $supplyRequest = DB::transaction(function () use ($request, $module) {
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

            return $supplyRequest;
        });

        $this->notifyQualityReviewers($supplyRequest);

        return redirect()->route('supplies.index', ['module' => $module])
            ->with('success', 'Solicitud enviada correctamente a Calidad.');
    }

    public function qualityIndex(string $module)
    {
        $requests = SupplyRequest::where('area_key', $module)
            ->where('status', 'pendiente_calidad')
            ->latest()
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
        abort_unless($supplyRequest->area_key === $module, 404);
        abort_if($supplyRequest->status !== 'pendiente_calidad', 403, 'Esta solicitud ya fue procesada por Calidad.');

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

        abort_if(
            $supplyRequest->status !== 'pendiente_calidad',
            403,
            'Esta solicitud ya fue procesada por Calidad.'
        );

        abort_unless($supplyRequest->area_key === $module, 404);

        DB::transaction(function () use ($request, $supplyRequest) {
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
        $requests = SupplyRequest::where('area_key', $module)
            ->whereIn('status', ['aprobada_calidad', 'en_compras', 'completada'])
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
        abort_unless($supplyRequest->area_key === $module, 404);
        abort_unless(
            in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras'], true),
            403,
            'Esta solicitud no esta disponible para costeo.'
        );

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

        abort_unless(
            in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras'], true),
            403,
            'Esta solicitud ya fue completada.'
        );

        abort_unless($supplyRequest->area_key === $module, 404);

        DB::transaction(function () use ($request, $supplyRequest) {
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

    /**
     * Solo el solicitante duenio o los perfiles de revision (Calidad, Compras,
     * administracion de usuarios y super-admin) pueden ver el detalle de una solicitud.
     */
    private function authorizeSupplyView(SupplyRequest $supplyRequest): void
    {
        $user = auth()->user();

        $canReview = $user->can('supply.tab.quality')
            || $user->can('approve.supply.quality')
            || $user->can('supply.tab.purchasing')
            || $user->can('manage.supply.purchasing')
            || $user->can('manage.users');

        abort_unless($supplyRequest->user_id === $user->id || $canReview, 403);
    }

    private function notifyQualityReviewers(SupplyRequest $supplyRequest): void
    {
        try {
            $emails = User::query()
                ->where('is_active', true)
                ->permission(['supply.tab.quality', 'approve.supply.quality'])
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($emails === []) {
                $emails = [env('ADMIN_EMAIL', 'admin@sjseguridad.local')];
            }

            Mail::to($emails)->send(new SupplyRequestNotification($supplyRequest));
        } catch (\Throwable $exception) {
            Log::error('Error enviando correos de suministros: '.$exception->getMessage());
        }
    }
}
