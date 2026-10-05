<?php

namespace App\Models;

use Database\Factories\ClienteInternoSolicitudFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteInternoSolicitud extends Model
{
    /** @use HasFactory<ClienteInternoSolicitudFactory> */
    use HasFactory;

    protected $table = 'cliente_interno_solicitudes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fecha_solicitud',
        'anio',
        'mes',
        'nombre_apellidos',
        'cedula',
        'correo_electronico',
        'tipo_solicitud_id',
        'fecha_respuesta',
        'estado_id',
        'novedad',
        'dias_respuesta',
        'dias_respuesta_manual',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'dias_respuesta_manual' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'fecha_respuesta' => 'date',
            'anio' => 'integer',
            'mes' => 'integer',
            'dias_respuesta' => 'integer',
            'dias_respuesta_manual' => 'boolean',
        ];
    }

    public function tipoSolicitud(): BelongsTo
    {
        return $this->belongsTo(ClienteInternoTipoSolicitud::class, 'tipo_solicitud_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(ClienteInternoEstado::class, 'estado_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
