<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioClienteCasoObservacion extends Model
{
    protected $table = 'servicio_cliente_caso_observaciones';

    protected $fillable = [
        'caso_id',
        'texto',
        'created_by',
    ];

    public function caso(): BelongsTo
    {
        return $this->belongsTo(ServicioClienteCaso::class, 'caso_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
