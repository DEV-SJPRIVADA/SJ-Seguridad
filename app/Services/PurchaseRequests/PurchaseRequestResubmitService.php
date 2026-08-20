<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestResubmitService
{
    public function resubmit(
        PurchaseRequest $purchaseRequest,
        array $validated,
        Request $request,
        User $aprobador,
    ): PurchaseRequest {
        $itemsInput = $request->input('items', []);
        $totalCantidad = collect($itemsInput)->sum(fn ($item) => (int) ($item['cantidad'] ?? 0));
        $lineCount = count($itemsInput);

        if ($request->hasFile('archivo_pedido')) {
            $archivoPedido = $request->file('archivo_pedido')->store('purchase-requests', 'public');
        } else {
            $archivoPedido = $purchaseRequest->archivo_pedido_path;
        }

        DB::transaction(function () use ($purchaseRequest, $validated, $request, $aprobador, $itemsInput, $totalCantidad, $archivoPedido): void {
            $purchaseRequest->items()->delete();

            $purchaseRequest->update([
                'area_key' => $validated['area_key'],
                'fecha_solicitud' => $validated['fecha_solicitud'],
                'cantidad' => max(1, $totalCantidad),
                'archivo_pedido_path' => $archivoPedido,
                'solicitud_para' => $validated['solicitud_para'],
                'urgente' => (bool) $validated['urgente'],
                'aprobador_id' => $aprobador->id,
                'proyecto_nuevo' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['proyecto_nuevo'] ?? false) : null,
                'razon_social' => $validated['solicitud_para'] === 'Cliente' ? ($validated['razon_social'] ?? null) : null,
                'asume_cliente' => $validated['solicitud_para'] === 'Cliente' ? (bool) ($validated['asume_cliente'] ?? false) : null,
                'estado' => PurchaseRequest::ESTADO_PENDIENTE,
                'estado_compras' => null,
                'fecha_aprobacion' => null,
                'comentarios_director' => null,
                'procesado_compras_at' => null,
                'procesado_compras_por' => null,
                'comentarios_compras' => null,
            ]);

            foreach ($itemsInput as $index => $itemData) {
                $fotoPath = $this->resolveItemPhotoPath($request, (int) $index, $itemData);

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'orden' => $index + 1,
                    'cantidad' => (int) $itemData['cantidad'],
                    'foto_path' => $fotoPath,
                    'descripcion' => $itemData['descripcion'],
                    'referencia' => $itemData['referencia'],
                    'utilizacion' => $itemData['utilizacion'],
                    'ubicacion' => $itemData['ubicacion'],
                ]);
            }
        });

        return $purchaseRequest->fresh(['user', 'aprobador', 'items']);
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    private function resolveItemPhotoPath(Request $request, int $index, array $itemData): ?string
    {
        if ($request->hasFile("items.{$index}.foto")) {
            return $request->file("items.{$index}.foto")->store('purchase-request-items', 'public');
        }

        $existingPath = trim((string) ($itemData['existing_foto_path'] ?? ''));

        if ($existingPath !== '' && ! str_contains($existingPath, '..')) {
            return $existingPath;
        }

        return null;
    }
}
