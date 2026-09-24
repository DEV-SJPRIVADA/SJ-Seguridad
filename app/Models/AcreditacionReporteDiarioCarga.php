<?php

namespace App\Models;

use Database\Factories\AcreditacionReporteDiarioCargaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcreditacionReporteDiarioCarga extends Model
{
    /** @use HasFactory<AcreditacionReporteDiarioCargaFactory> */
    use HasFactory;

    protected $table = 'acreditacion_reporte_diario_cargas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fecha_reporte',
        'proceso_file_name',
        'proceso_rows_ok',
        'proceso_rows_fail',
        'proceso_loaded_at',
        'proceso_loaded_by',
        'acreditado_file_name',
        'acreditado_rows_ok',
        'acreditado_rows_fail',
        'acreditado_loaded_at',
        'acreditado_loaded_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'proceso_rows_ok' => 0,
        'proceso_rows_fail' => 0,
        'acreditado_rows_ok' => 0,
        'acreditado_rows_fail' => 0,
    ];

    protected function casts(): array
    {
        return [
            'fecha_reporte' => 'date',
            'proceso_rows_ok' => 'integer',
            'proceso_rows_fail' => 'integer',
            'proceso_loaded_at' => 'datetime',
            'acreditado_rows_ok' => 'integer',
            'acreditado_rows_fail' => 'integer',
            'acreditado_loaded_at' => 'datetime',
        ];
    }

    public function filas(): HasMany
    {
        return $this->hasMany(AcreditacionReporteDiarioFila::class, 'carga_id');
    }

    public function procesoLoadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proceso_loaded_by');
    }

    public function acreditadoLoadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acreditado_loaded_by');
    }

    public function origenHasPriorData(string $origen): bool
    {
        if ($origen === AcreditacionReporteDiarioFila::ORIGEN_PROCESO) {
            return $this->proceso_loaded_at !== null
                || (int) $this->proceso_rows_ok > 0
                || $this->filas()->where('origen', AcreditacionReporteDiarioFila::ORIGEN_PROCESO)->exists();
        }

        if ($origen === AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO) {
            return $this->acreditado_loaded_at !== null
                || (int) $this->acreditado_rows_ok > 0
                || $this->filas()->where('origen', AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO)->exists();
        }

        return false;
    }
}
