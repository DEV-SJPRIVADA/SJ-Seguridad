<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = 1;
        $valorUnidad = 100;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'orden' => 1,
            'cantidad' => $cantidad,
            'articulo' => 'REF-001',
            'descripcion' => 'PRODUCTO DE PRUEBA',
            'valor_unidad' => $valorUnidad,
            'iva' => 0,
            'valor_total' => $cantidad * $valorUnidad,
            'source_item_type' => null,
            'source_item_id' => null,
        ];
    }
}
