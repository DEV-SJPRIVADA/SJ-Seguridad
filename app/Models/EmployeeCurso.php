<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EmployeeCursoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EmployeeCurso extends Model
{
    /** @use HasFactory<EmployeeCursoFactory> */
    use HasFactory;

    protected $table = 'employee_cursos';

    public const ESTADO_SOLICITADO = 'SOLICITADO';

    public const ESTADO_ACTUALIZADO = 'ACTUALIZADO';

    public const ESTADO_PENDIENTE = 'PENDIENTE';

    public const VIGENCIA_VIGENTE = 'VIGENTE';

    public const VIGENCIA_ACTUALIZAR = 'ACTUALIZAR';

    public const VIGENCIA_VENCIDO = 'VENCIDO';

    /**
     * @var list<string>
     */
    public const ESTADOS = [
        self::ESTADO_SOLICITADO,
        self::ESTADO_ACTUALIZADO,
        self::ESTADO_PENDIENTE,
    ];

    /**
     * @var list<string>
     */
    public const VIGENCIAS = [
        self::VIGENCIA_VIGENTE,
        self::VIGENCIA_ACTUALIZAR,
        self::VIGENCIA_VENCIDO,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'full_name',
        'curso_tipo_id',
        'fecha_expedicion',
        'numero_curso',
        'estado',
        'observaciones',
        'document_path',
        'document_original_name',
        'document_mime',
        'document_size_bytes',
        'employee_ficha_profile_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_expedicion' => 'date',
            'document_size_bytes' => 'integer',
        ];
    }

    public function cursoTipo(): BelongsTo
    {
        return $this->belongsTo(CursoTipo::class, 'curso_tipo_id');
    }

    public function employeeFichaProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeFichaProfile::class, 'employee_ficha_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasDocument(): bool
    {
        return filled($this->document_path);
    }

    /**
     * Umbral de alerta ACTUALIZAR: (hoy + 30 días) − 365 días ≡ hoy − 335 días.
     */
    public static function vigenciaThreshold(?CarbonInterface $today = null): CarbonInterface
    {
        $today ??= Carbon::today();

        return $today->copy()->startOfDay()->addDays(30)->subDays(365);
    }

    /**
     * Umbral VENCIDO: hoy − 1 año (equivalente a fecha_expedicion + 1 año ≤ hoy).
     */
    public static function vencidoThreshold(?CarbonInterface $today = null): CarbonInterface
    {
        $today ??= Carbon::today();

        return $today->copy()->startOfDay()->subYear();
    }

    public function computeVigencia(?CarbonInterface $today = null): string
    {
        $fecha = $this->fecha_expedicion;

        if ($fecha === null) {
            return self::VIGENCIA_ACTUALIZAR;
        }

        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $fecha = $fecha->copy()->startOfDay();

        if ($fecha->lte(self::vencidoThreshold($today))) {
            return self::VIGENCIA_VENCIDO;
        }

        if ($fecha->lt(self::vigenciaThreshold($today))) {
            return self::VIGENCIA_ACTUALIZAR;
        }

        return self::VIGENCIA_VIGENTE;
    }

    protected function vigencia(): Attribute
    {
        return Attribute::get(fn (): string => $this->computeVigencia());
    }

    public function scopeForDocumentNumber(Builder $query, string $documentNumber): Builder
    {
        return $query->where('document_number', $documentNumber);
    }
}
