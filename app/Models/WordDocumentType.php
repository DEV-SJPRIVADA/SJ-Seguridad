<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WordDocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(TerminationLetterDocumentTemplate::class, 'word_document_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}
