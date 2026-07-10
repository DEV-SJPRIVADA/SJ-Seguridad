<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorSystemDocumentVersion extends Model
{
    protected $fillable = [
        'indicator_system_document_id',
        'version_number',
        'status',
        'content',
        'change_summary',
        'change_reason',
        'author_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(IndicatorSystemDocument::class, 'indicator_system_document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
