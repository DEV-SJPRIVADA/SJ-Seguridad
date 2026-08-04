<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'orden',
        'cantidad',
        'foto_path',
        'descripcion',
        'referencia',
        'utilizacion',
        'ubicacion',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function fotoUrl(): ?string
    {
        if ($this->foto_path === null || $this->foto_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->foto_path);
    }
}
