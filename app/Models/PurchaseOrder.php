<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source_type // morphTo: purchase_request | supply_request
 * @property int $source_id // PK del origen
 * @property int $provider_id
 * @property string $provider_identificacion
 * @property string $provider_nombre
 * @property string|null $provider_telefono
 * @property string|null $provider_correo
 * @property string|null $provider_contacto
 * @property string|null $provider_direccion
 * @property string|null $provider_ciudad
 * @property string|null $provider_forma_pago
 * @property int|null $provider_dias_pago
 * @property string $fecha // fecha de emisión
 * @property string|null $ciudad // cabecera OC (ciudad de entrega)
 * @property string|null $asume_cliente // desde origen compra si aplica
 * @property string|null $observaciones
 * @property string $estado // borrador | emitida
 * @property float $total
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseProvider $provider
 * @property-read HasMany $items
 * @property-read PurchaseOrderEvaluation|null $evaluation
 * @property-read MorphTo $source
 */
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'source_type',
        'source_id',
        'provider_id',
        'provider_identificacion',
        'provider_nombre',
        'provider_telefono',
        'provider_correo',
        'provider_contacto',
        'provider_direccion',
        'provider_ciudad',
        'provider_forma_pago',
        'provider_dias_pago',
        'fecha',
        'ciudad',
        'asume_cliente',
        'observaciones',
        'estado',
        'total',
        'created_by',
    ];

    protected $casts = [
        'provider_dias_pago' => 'integer',
        'total' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PurchaseProvider::class, 'provider_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id')->orderBy('orden');
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(PurchaseOrderEvaluation::class, 'purchase_order_id');
    }

    /**
     * Fuente polimórfica: PurchaseRequest o SupplyRequest.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Número OC visible (regla: id == número OC, sin prefijo ni pad).
     */
    public function getNumeroOcAttribute(): int
    {
        return (int) $this->attributes['id'];
    }

    public function estadoLabel(): string
    {
        return (string) (config('purchase-orders.estados')[$this->estado] ?? ucfirst((string) $this->estado));
    }

    public function sourceLabel(): string
    {
        return match ($this->source_type) {
            'purchase_request', PurchaseRequest::class => 'Solicitud de compra',
            'supply_request', SupplyRequest::class => 'Solicitud de suministro',
            default => ucfirst(class_basename((string) $this->source_type)),
        };
    }

    public function sourceFolio(): string
    {
        return match (true) {
            $this->source instanceof PurchaseRequest => $this->source->folio(),
            $this->source instanceof SupplyRequest => $this->source->folio(),
            default => (string) $this->source_id,
        };
    }
}
