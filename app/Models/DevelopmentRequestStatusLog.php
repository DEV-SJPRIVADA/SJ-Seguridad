<?php

namespace App\Models;

use Database\Factories\DevelopmentRequestStatusLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentRequestStatusLog extends Model
{
    /** @use HasFactory<DevelopmentRequestStatusLogFactory> */
    use HasFactory;

    protected $fillable = [
        'development_request_id',
        'user_id',
        'from_status',
        'to_status',
        'comment',
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
