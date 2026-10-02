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

    /**
     * @var array<string, string>
     */
    public const ESTADO_LABELS = [
        self::ESTADO_APROBADO => 'Aprobado',
        self::ESTADO_REPROBADO => 'Reprobado',
        self::ESTADO_NO_REALIZADA => 'No realizada',
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
