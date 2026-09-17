<?php

namespace App\Models;

use Database\Factories\DevelopmentRequestMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentRequestMessage extends Model
{
    /** @use HasFactory<DevelopmentRequestMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'development_request_id',
        'user_id',
        'body',
        'attachment_path',
    ];

    public function developmentRequest(): BelongsTo
    {
        return $this->belongsTo(DevelopmentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
