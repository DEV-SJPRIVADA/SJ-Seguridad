<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityDocumentArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_document_id',
        'area_key',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(QualityDocument::class, 'quality_document_id');
    }
}
