<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialClientDocumentItem extends Model
{
    protected $fillable = [
        'commercial_client_id',
        'document_key',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CommercialClient::class, 'commercial_client_id');
    }
}
