<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicioClienteCaso extends Model
{
    public const TIPO_INTERNO = 'interno';

    public const TIPO_EXTERNO = 'externo';

    public const EFICACIA_ABIERTO = 'abierto';

    public const EFICACIA_EN_PROCESO = 'en_proceso';

    public const EFICACIA_CERRADO = 'cerrado';

    protected $fillable = [
        'consecutivo',
        'fecha_recepcion',
        'fecha_ejecucion',
        'fecha_verificacion',
        'fecha_plazo_respuesta',
        'tipo_cliente',
        'tipologia_id',
        'fuente_id',
        'commercial_client_id',
        'cliente_nombre',
        'city_id',
        'descripcion',
        'categoria_id',
        'correccion',
        'accion_realizar',
        'responsable_id',
        'dias_habiles',
        'responsable_seguimiento',
        'eficacia',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consecutivo' => 'integer',
            'fecha_recepcion' => 'date',
            'fecha_ejecucion' => 'date',
            'fecha_verificacion' => 'date',
            'fecha_plazo_respuesta' => 'date',
            'dias_habiles' => 'integer',
        ];
    }

    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(ServicioClienteTipologia::class, 'tipologia_id');
    }

    public function fuente(): BelongsTo
    {
        return $this->belongsTo(ServicioClienteFuente::class, 'fuente_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(ServicioClienteCategoria::class, 'categoria_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(ServicioClienteResponsable::class, 'responsable_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(CommercialClient::class, 'commercial_client_id');
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(RequisitionCity::class, 'city_id');
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(ServicioClienteCasoObservacion::class, 'caso_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(ServicioClienteCasoAdjunto::class, 'caso_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function eficaciaLabel(): string
    {
        return (string) config('servicio_cliente.eficacia.'.$this->eficacia, $this->eficacia);
    }

    public function tipoClienteLabel(): string
    {
        return (string) config('servicio_cliente.tipos_cliente.'.$this->tipo_cliente, $this->tipo_cliente);
    }
}
