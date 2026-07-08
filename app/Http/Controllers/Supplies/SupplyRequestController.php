<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Mail\SupplyRequestNotification;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
        $validated = $this->validateStoreItems($request);

        $supplyRequest = DB::transaction(function () use ($validated, $module) {
            $supplyRequest = SupplyRequest::create([
                'user_id' => auth()->id(),
                'area_key' => $module,
                'status' => 'pendiente_calidad',
                'observations' => $validated['observations'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                if (($item['type'] ?? '') === 'custom') {
                    $supplyRequest->items()->create([
                        'custom_product_name' => $item['custom_name'],
                        'is_not_in_catalog' => true,
                        'current_inventory' => 0,
                        'requested_quantity' => $item['quantity'],
                    ]);
                } else {
                    $supplyRequest->items()->create([
                        'supply_product_id' => $item['product_id'],
                        'is_not_in_catalog' => false,
                        'current_inventory' => $item['current_inventory'],
                        'requested_quantity' => $item['quantity'],
                    ]);
                }
            }

            return $supplyRequest;
        });

        $this->notifyApprovalReviewers($supplyRequest);

        return redirect()->route('supplies.index', ['module' => $module])
            ->with('success', 'Solicitud enviada correctamente a aprobacion de insumos.');
    }

    public function approvalIndex(string $module)
    {
        $requests = SupplyRequest::where('area_key', $module)
            ->where('status', 'pendiente_calidad')
            ->latest()
            ->with(['user', 'items.product'])
            ->get();

        return view('modules.supplies.approval.index', [
            'module' => $module,
            'requests' => $requests,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function approvalEdit(string $module, SupplyRequest $supplyRequest)
    {
        abort_unless($supplyRequest->area_key === $module, 404);
        abort_if($supplyRequest->status !== 'pendiente_calidad', 403, 'Esta solicitud ya fue procesada.');

        $supplyRequest->load(['user', 'items.product']);

        return view('modules.supplies.approval.edit', [
            'module' => $module,
            'request' => $supplyRequest,
            'subTabs' => $this->getSupplySubTabs($module),
        ]);
    }

    public function approvalUpdate(Request $request, string $module, SupplyRequest $supplyRequest)
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
            'Esta solicitud ya fue procesada.'
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

        return redirect()->route('supplies.approval.index', ['module' => $module])
            ->with('success', 'Solicitud procesada correctamente.');
    }

    private function authorizeSupplyView(SupplyRequest $supplyRequest): void
    {
        $user = auth()->user();

        $canReview = $user->can('supply.tab.quality')
            || $user->can('approve.supply.quality')
            || $user->can('manage.users');

        abort_unless($supplyRequest->user_id === $user->id || $canReview, 403);
    }

    /**
     * @return array{observations: ?string, items: array<int, array<string, mixed>>}
     */
    private function validateStoreItems(Request $request): array
    {
        $request->validate([
            'observations' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:catalog,custom',
        ]);

        $items = $request->input('items', []);
        $errors = [];

        foreach ($items as $index => $item) {
            if (($item['type'] ?? '') === 'catalog') {
                $validator = Validator::make($item, [
                    'product_id' => 'required|exists:supply_products,id',
                    'current_inventory' => 'required|integer|min:0',
                    'quantity' => 'required|integer|min:1',
                ]);
            } else {
                $validator = Validator::make($item, [
                    'custom_name' => 'required|string|max:255',
                    'quantity' => 'required|integer|min:1',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors["items.{$index}.{$field}"][] = $message;
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'observations' => $request->input('observations'),
            'items' => $items,
        ];
    }

    private function notifyApprovalReviewers(SupplyRequest $supplyRequest): void
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
