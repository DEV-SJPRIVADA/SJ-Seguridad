<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminationLetterDocumentTemplate extends Model
{
    protected $fillable = [
        'word_document_type_id',
        'label',
        'sort_order',
        'template_path',
    ];

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(WordDocumentType::class, 'word_document_type_id');
    }

    public function scopeForTypeCode(Builder $query, string $typeCode): Builder
    {
        return $query->whereHas('type', static function (Builder $typeQuery) use ($typeCode): void {
            $typeQuery->where('code', $typeCode);
        });
    }

    public function scopeWithFile(Builder $query): Builder
    {
        return $query
            ->whereNotNull('template_path')
            ->where('template_path', '!=', '');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function hasTemplateFile(): bool
    {
        return filled($this->template_path);
    }
}
