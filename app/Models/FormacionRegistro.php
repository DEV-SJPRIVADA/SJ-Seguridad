<?php

namespace App\Models;

use Database\Factories\FormacionRegistroFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormacionRegistro extends Model
{
    /** @use HasFactory<FormacionRegistroFactory> */
    use HasFactory;

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_REPROBADO = 'reprobado';

    public const ESTADO_NO_REALIZADA = 'no_realizada';

    public const CICLO_APROBADO = 'aprobado';

    public const CICLO_REPROBADO = 'reprobado';

    public const CICLO_INCOMPLETO = 'incompleto';

    public const CICLO_NO_REALIZADO = 'no_realizado';

    /**
     * @var array<string, string>
     */
    public const ESTADO_LABELS = [
        self::ESTADO_APROBADO => 'Aprobado',
        self::ESTADO_REPROBADO => 'Reprobado',
        self::ESTADO_NO_REALIZADA => 'No realizada',
    ];

    /**
     * @var array<string, string>
     */
    public const CICLO_LABELS = [
        self::CICLO_APROBADO => 'Aprobado',
        self::CICLO_REPROBADO => 'Reprobado',
        self::CICLO_INCOMPLETO => 'Incompleto',
        self::CICLO_NO_REALIZADO => 'No realizado',
    ];

    protected $table = 'formacion_registros';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'numero_id',
        'nombre_completo',
        'fecha_inicio',
        'mes',
        'anio',
        'nombre_curso',
        'calificacion',
        'categoria',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'mes' => 'integer',
            'anio' => 'integer',
        ];
    }

    /**
     * Estado derivado de calificación: vacío → No realizada; > 7.5 → Aprobado; resto numérico → Reprobado.
     */
    public function estadoLabel(): string
    {
        return self::ESTADO_LABELS[$this->estadoKey()] ?? self::ESTADO_LABELS[self::ESTADO_NO_REALIZADA];
    }

    public function estadoKey(): string
    {
        return self::estadoKeyFromCalificacion($this->calificacion);
    }

    public function estadoCssClass(): string
    {
        return match ($this->estadoKey()) {
            self::ESTADO_APROBADO => 'status-pill status-pill--success',
            self::ESTADO_REPROBADO => 'status-pill status-pill--danger',
            default => 'status-pill status-pill--muted',
        };
    }

    public static function estadoFromCalificacion(mixed $calificacion): string
    {
        return self::ESTADO_LABELS[self::estadoKeyFromCalificacion($calificacion)]
            ?? self::ESTADO_LABELS[self::ESTADO_NO_REALIZADA];
    }

    public static function estadoKeyFromCalificacion(mixed $calificacion): string
    {
        $raw = trim((string) ($calificacion ?? ''));

        if ($raw === '') {
            return self::ESTADO_NO_REALIZADA;
        }

        $normalized = str_replace(',', '.', $raw);
        if (! is_numeric($normalized)) {
            return self::ESTADO_NO_REALIZADA;
        }

        return (float) $normalized > 7.5
            ? self::ESTADO_APROBADO
            : self::ESTADO_REPROBADO;
    }

    /**
     * Mejor calificación numérica (máximo). Vacío / no numérico → null.
     */
    public static function numericCalificacion(mixed $calificacion): ?float
    {
        $raw = trim((string) ($calificacion ?? ''));
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $raw);
        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Clasificación de persona en el ciclo (set de cursos del periodo).
     *
     * @param  list<string>  $courseStatuses  claves ESTADO_* por cada curso del set
     */
    public static function cicloStatusFromCourseStatuses(array $courseStatuses): string
    {
        if ($courseStatuses === []) {
            return self::CICLO_NO_REALIZADO;
        }

        $aprobados = 0;
        $reprobados = 0;
        $noRealizadas = 0;

        foreach ($courseStatuses as $status) {
            match ($status) {
                self::ESTADO_APROBADO => $aprobados++,
                self::ESTADO_REPROBADO => $reprobados++,
                default => $noRealizadas++,
            };
        }

        if ($reprobados > 0) {
            return self::CICLO_REPROBADO;
        }

        if ($noRealizadas === count($courseStatuses)) {
            return self::CICLO_NO_REALIZADO;
        }

        if ($aprobados === count($courseStatuses)) {
            return self::CICLO_APROBADO;
        }

        return self::CICLO_INCOMPLETO;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithEstado(Builder $query, string $estadoKey): Builder
    {
        $estadoKey = strtolower(trim($estadoKey));

        if (! array_key_exists($estadoKey, self::ESTADO_LABELS)) {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();
        $numExpr = "REPLACE(TRIM(COALESCE(calificacion, '')), ',', '.')";
        $blank = "(calificacion IS NULL OR TRIM(calificacion) = '')";
        $hasDigit = $driver === 'sqlite'
            ? 'TRIM(COALESCE(calificacion, \'\')) GLOB \'*[0-9]*\''
            : 'TRIM(COALESCE(calificacion, \'\')) REGEXP \'[0-9]\'';
        $numericValue = "CAST({$numExpr} AS DECIMAL(10,4))";

        return match ($estadoKey) {
            self::ESTADO_APROBADO => $query
                ->whereRaw("NOT {$blank}")
                ->whereRaw($hasDigit)
                ->whereRaw("{$numericValue} > 7.5"),
            self::ESTADO_REPROBADO => $query
                ->whereRaw("NOT {$blank}")
                ->whereRaw($hasDigit)
                ->whereRaw("{$numericValue} <= 7.5"),
            self::ESTADO_NO_REALIZADA => $query->where(function (Builder $inner) use ($blank, $hasDigit): void {
                $inner->whereRaw($blank)
                    ->orWhereRaw("NOT ({$hasDigit})");
            }),
            default => $query,
        };
    }
}
