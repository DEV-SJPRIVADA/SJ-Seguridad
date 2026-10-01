<?php

namespace App\Models;

use Database\Factories\FormacionRegistroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormacionRegistro extends Model
{
    /** @use HasFactory<FormacionRegistroFactory> */
    use HasFactory;

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
}
