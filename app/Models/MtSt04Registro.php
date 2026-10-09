<?php

namespace App\Models;

use Database\Factories\MtSt04RegistroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MtSt04Registro extends Model
{
    /** @use HasFactory<MtSt04RegistroFactory> */
    use HasFactory;

    public const ARMA_SI = 'SI';

    public const ARMA_NO = 'NO';

    public const APTO_SI = 'SI';

    public const APTO_NO = 'NO';

    public const ESTADO_VIGENTE = 'VIGENTE';

    public const ESTADO_VENCERA = 'VENCERA';

    public const ESTADO_VENCIDO = 'VENCIDO';

    public const ESTADO_NO_APLICA = 'NO APLICA';

    protected $table = 'mt_st_04_registros';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'arma',
        'fecha_examen_1',
        'fecha_vencimiento_1',
        'apto',
        'observaciones_1',
        'estado_1',
        'fecha_examen_2',
        'fecha_vencimiento_2',
        'observaciones_2',
        'estado_2',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_examen_1' => 'date',
            'fecha_vencimiento_1' => 'date',
            'fecha_examen_2' => 'date',
            'fecha_vencimiento_2' => 'date',
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
