<?php

namespace App\Models;

use Database\Factories\AcreditacionAcreditadoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcreditacionAcreditado extends Model
{
    /** @use HasFactory<AcreditacionAcreditadoFactory> */
    use HasFactory;

    protected $table = 'acreditacion_acreditados';

    public const ESTADO_EN_PROCESO = 'EN_PROCESO';

    public const ESTADO_DESACREDITADO = 'DESACREDITADO';

    public const ESTADO_POR_VENCER = 'POR_VENCER';

    public const ESTADO_ACREDITADO = 'ACREDITADO';

    public const RENOVACION_SOLICITADO = 'SOLICITADO';

    public const RENOVACION_RENOVADO = 'RENOVADO';

    /**
     * @var list<string>
     */
    public const ESTADOS = [
        self::ESTADO_EN_PROCESO,
        self::ESTADO_DESACREDITADO,
        self::ESTADO_POR_VENCER,
        self::ESTADO_ACREDITADO,
    ];

    /**
     * @var list<string>
     */
    public const RENOVACIONES = [
        self::RENOVACION_SOLICITADO,
        self::RENOVACION_RENOVADO,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'full_name',
        'cargo',
        'cargo_apo',
        'vigencia_acr',
        'fecha_solicitud',
        'estado',
        'renovacion',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'vigencia_acr' => 'date',
            'fecha_solicitud' => 'date',
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

    public function scopeForCargoApo(Builder $query, string $cargoApo): Builder
    {
        return $query->whereRaw('LOWER(TRIM(cargo_apo)) = ?', [mb_strtolower(trim($cargoApo))]);
    }

    public function estadoLabel(): string
    {
        /** @var array<string, string> $labels */
        $labels = config('acreditaciones.estados', []);

        return $labels[$this->estado] ?? (string) $this->estado;
    }

    public function renovacionLabel(): string
    {
        if ($this->renovacion === null || $this->renovacion === '') {
            return '—';
        }

        /** @var array<string, string> $labels */
        $labels = config('acreditaciones.renovaciones', []);

        return $labels[$this->renovacion] ?? (string) $this->renovacion;
    }
}
