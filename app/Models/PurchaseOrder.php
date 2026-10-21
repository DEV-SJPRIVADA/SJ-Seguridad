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
use Throwable;

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
 * @property string|null $asume_cliente // snapshot SI/NO o null
 * @property string|null $proyecto_nuevo // snapshot SI/NO o null
 * @property string|null $reinversion // snapshot SI/NO o null
 * @property string|null $comodato // snapshot SI/NO o null
 * @property string|null $activo // snapshot SI/NO o null
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
        'proyecto_nuevo',
        'reinversion',
        'comodato',
        'activo',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Relaciones que usan el PDF y el Excel del FO-AD-07.
     */
    public function loadDocumentRelations(): self
    {
        $this->loadMissing(['items', 'createdBy', 'source']);

        $source = $this->source;

        if ($source instanceof PurchaseRequest) {
            $source->loadMissing('aprobador');
        } elseif ($source instanceof SupplyRequest) {
            $source->loadMissing('qualityReviewer');
        }

        return $this;
    }

    public function fechaImpresa(): string
    {
        $fecha = trim((string) $this->fecha);

        if ($fecha === '') {
            return '';
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (Throwable) {
            return $fecha;
        }
    }

    /**
     * Sí o No del snapshot (SI/NO). Null no imprime texto.
     */
    public function imprimirSiNo(?string $snapshot): string
    {
        return match ($snapshot) {
            'SI' => 'Sí',
            'NO' => 'No',
            default => '',
        };
    }

    /**
     * Asume el cliente, Proyecto nuevo, Reinversión, Comodato y Activo
     * se leen de la solicitud de compra. Sí solo si está marcado; si no, No.
     */
    public function banderaImpresa(string $campo): string
    {
        $this->loadMissing('source');
        $source = $this->source;

        if ($source instanceof PurchaseRequest) {
            return $source->{$campo} === true ? 'Sí' : 'No';
        }

        $desdeSnapshot = $this->imprimirSiNo($this->{$campo});

        return $desdeSnapshot !== '' ? $desdeSnapshot : 'No';
    }

    public function formaPagoImpresa(): string
    {
        $forma = trim((string) $this->provider_forma_pago);

        if ($forma !== '') {
            return $forma;
        }

        if ($this->provider_dias_pago === null) {
            return '';
        }

        return 'A '.$this->provider_dias_pago.' DÍAS';
    }

    public function elaboradoNombre(): string
    {
        return trim((string) ($this->createdBy?->name ?? ''));
    }

    /**
     * Director que autorizó la compra, o revisor de Calidad en un suministro.
     */
    public function revisorNombre(): string
    {
        $source = $this->source;

        if ($source instanceof PurchaseRequest) {
            return trim((string) ($source->aprobador?->name ?? ''));
        }

        if ($source instanceof SupplyRequest) {
            return trim((string) ($source->qualityReviewer?->name ?? ''));
        }

        return '';
    }

    public function sourceFolio(): string
    {
        return match (true) {
            $this->source instanceof PurchaseRequest => $this->source->folioVisible(),
            $this->source instanceof SupplyRequest => $this->source->folioVisible(),
            default => (string) $this->source_id,
        };
    }
}
