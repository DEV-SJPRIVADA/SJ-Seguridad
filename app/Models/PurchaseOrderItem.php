<?php

namespace App\Models;

use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $orden
 * @property float $cantidad
 * @property string|null $articulo
 * @property string|null $descripcion
 * @property float $valor_unidad
 * @property float $iva
 * @property float $valor_total
 * @property string|null $source_item_type
 * @property int|null $source_item_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseOrder $purchaseOrder
 */
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'orden',
        'cantidad',
        'articulo',
        'descripcion',
        'valor_unidad',
        'iva',
        'iva_porcentaje',
        'valor_total',
        'source_item_type',
        'source_item_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'valor_unidad' => 'decimal:2',
        'iva' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    /**
     * IVA para mostrar: valor y, si se eligió tasa, el porcentaje. Ej: "19.00 (19%)".
     */
    public function ivaImpreso(): string
    {
        $valor = number_format((float) $this->iva, 2);

        return $this->iva_porcentaje !== null
            ? $valor.' ('.PurchaseTaxRate::formatPorcentaje($this->iva_porcentaje).')'
            : $valor;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
