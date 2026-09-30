<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportesNovedadesPermiso extends Model
{
    protected $table = 'reportes_novedades_permisos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'employee_name',
        'tipo',
        'cargo',
        'novedad',
        'dias_novedad',
        'fecha_inicio',
        'fecha_fin',
        'marca_gh',
        'observacion_nomina',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'marca_gh' => false,
    ];

    protected function casts(): array
    {
        return [
            'dias_novedad' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'marca_gh' => 'boolean',
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
