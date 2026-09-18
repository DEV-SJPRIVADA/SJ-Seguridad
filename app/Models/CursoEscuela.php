<?php

namespace App\Models;

use Database\Factories\CursoEscuelaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CursoEscuela extends Model
{
    /** @use HasFactory<CursoEscuelaFactory> */
    use HasFactory;

    protected $table = 'curso_escuelas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'nit',
        'nombre',
        'is_active',
        'created_by',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeeCursos(): HasMany
    {
        return $this->hasMany(EmployeeCurso::class, 'curso_escuela_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('codigo')->orderBy('id');
    }

    /**
     * Normaliza codigo de escuela para comparar (solo digitos, sin ceros a la izquierda).
     * Ej.: "015" y "0015" → "15".
     */
    public static function normalizeCodigo(string $codigo): string
    {
        $digits = preg_replace('/\D+/', '', trim($codigo)) ?? '';
        $normalized = ltrim($digits, '0');

        return $normalized === '' ? '' : $normalized;
    }

    /**
     * Extrae el codigo de escuela desde No.CURSO.
     * Ejemplo: ECSP0015-M256412 → "0015" (normalizado "15").
     */
    public static function extractCodigoFromNumeroCurso(string $numeroCurso): ?string
    {
        $numeroCurso = trim($numeroCurso);
        if ($numeroCurso === '' || ! str_contains($numeroCurso, '-')) {
            return null;
        }

        $left = explode('-', $numeroCurso, 2)[0];
        if (! preg_match('/(\d+)\s*$/', $left, $matches)) {
            return null;
        }

        $normalized = self::normalizeCodigo($matches[1]);

        return $normalized === '' ? null : $normalized;
    }
}
