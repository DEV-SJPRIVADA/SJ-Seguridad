<?php

namespace App\Models;

use Database\Factories\PurchaseOrderEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_order_id // 1:1 por OC
 * @property Carbon|null $fecha_recibido
 * @property int|null $calif_referencias_cantidades // 1-5 (peso 25%)
 * @property int|null $calif_oportunidad_entrega // 1-5 (peso 25%)
 * @property int|null $calif_calidad_producto // 1-5 (peso 50%)
 * @property float|null $resultado_referencias
 * @property float|null $resultado_oportunidad
 * @property float|null $resultado_calidad
 * @property float|null $puntaje_porcentaje // 0-100
 * @property string|null $umbral_codigo // satisfactoria / seguimiento / insatisfactoria
 * @property string|null $observaciones
 * @property int|null $evaluated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseOrder $purchaseOrder
 */
class PurchaseOrderEvaluation extends Model
{
    /** @use HasFactory<PurchaseOrderEvaluationFactory> */
    use HasFactory;

    protected $table = 'purchase_order_evaluations';

    protected $fillable = [
        'purchase_order_id',
        'fecha_recibido',
        'calif_referencias_cantidades',
        'calif_oportunidad_entrega',
        'calif_calidad_producto',
        'resultado_referencias',
        'resultado_oportunidad',
        'resultado_calidad',
        'puntaje_porcentaje',
        'umbral_codigo',
        'observaciones',
        'evaluated_by',
    ];

    protected $casts = [
        'fecha_recibido' => 'date',
        'calif_referencias_cantidades' => 'integer',
        'calif_oportunidad_entrega' => 'integer',
        'calif_calidad_producto' => 'integer',
        'resultado_referencias' => 'decimal:2',
        'resultado_oportunidad' => 'decimal:2',
        'resultado_calidad' => 'decimal:2',
        'puntaje_porcentaje' => 'decimal:2',
        'evaluated_by' => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
