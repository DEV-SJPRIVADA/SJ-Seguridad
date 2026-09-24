<?php

namespace App\Models;

use Database\Factories\AcreditacionReporteDiarioFilaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcreditacionReporteDiarioFila extends Model
{
    /** @use HasFactory<AcreditacionReporteDiarioFilaFactory> */
    use HasFactory;

    protected $table = 'acreditacion_reporte_diario_filas';

    public const ORIGEN_PROCESO = 'PROCESO';

    public const ORIGEN_ACREDITADO = 'ACREDITADO';

    /**
     * @var list<string>
     */
    public const ORIGENES = [
        self::ORIGEN_PROCESO,
        self::ORIGEN_ACREDITADO,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'carga_id',
        'origen',
        'apellido1',
        'apellido2',
        'nombre1',
        'nombre2',
        'full_name',
        'document_number',
        'cargo',
        'estado_apo',
        'vigencia_acr',
        'source_row',
    ];

    protected function casts(): array
    {
        return [
            'vigencia_acr' => 'date',
            'source_row' => 'integer',
        ];
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(AcreditacionReporteDiarioCarga::class, 'carga_id');
    }

    public function origenLabel(): string
    {
        /** @var array<string, string> $labels */
        $labels = config('acreditaciones.reporte_diario.origenes', []);

        return $labels[$this->origen] ?? (string) $this->origen;
    }

    /**
     * Estado APO para UI/export: si hay Vigen.Acr y no hay texto de estado, se muestra ACREDITADO.
     */
    public function resolvedEstadoApo(): ?string
    {
        $estado = trim((string) ($this->estado_apo ?? ''));
        if ($estado !== '') {
            return $estado;
        }

        if ($this->vigencia_acr !== null) {
            return 'ACREDITADO';
        }

        return null;
    }
}
