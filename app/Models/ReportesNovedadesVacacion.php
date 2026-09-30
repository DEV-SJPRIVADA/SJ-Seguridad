<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportesNovedadesVacacion extends Model
{
    protected $table = 'reportes_novedades_vacaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'employee_name',
        'cargo',
        'destino',
        'novedad',
        'dias_novedad',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'observacion_nomina',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'dias_novedad' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
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
