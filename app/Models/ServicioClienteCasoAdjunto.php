<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioClienteCasoAdjunto extends Model
{
    protected $table = 'servicio_cliente_caso_adjuntos';

    protected $fillable = [
        'caso_id',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(ServicioClienteCaso::class, 'caso_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
