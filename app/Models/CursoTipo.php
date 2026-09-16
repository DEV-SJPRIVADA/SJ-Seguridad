<?php

namespace App\Models;

use Database\Factories\CursoTipoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CursoTipo extends Model
{
    /** @use HasFactory<CursoTipoFactory> */
    use HasFactory;

    protected $table = 'curso_tipos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tipo_curso',
        'cargo_curso',
        'formato_para_cursos',
        'cursos',
        'cargo_acredit',
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

    public function employeeCursos(): HasMany
    {
        return $this->hasMany(EmployeeCurso::class, 'curso_tipo_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('tipo_curso')->orderBy('id');
    }
}
