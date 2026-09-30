<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportesNovedadesIncapacidad extends Model
{
    protected $table = 'reportes_novedades_incapacidades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'employee_name',
        'cargo',
        'destino',
        'tipo_incapacidad',
        'dias',
        'fecha_inicio',
        'fecha_fin',
        'fecha_recepcion',
        'fecha_devolucion',
        'observacion_devolucion',
        'fecha_registro_control_roll',
        'fecha_envio_final',
        'novedad_control_roll',
        'extemporanea',
        'observaciones',
        'observacion_nomina',
        'dias_entrega',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'extemporanea' => false,
    ];

    protected function casts(): array
    {
        return [
            'dias' => 'integer',
            'dias_entrega' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_recepcion' => 'date',
            'fecha_devolucion' => 'date',
            'fecha_registro_control_roll' => 'date',
            'fecha_envio_final' => 'date',
            'extemporanea' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
