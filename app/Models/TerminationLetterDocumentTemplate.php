<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TerminationLetterDocumentTemplate extends Model
{
    protected $fillable = [
        'termination_cause_code',
        'document_key',
        'label',
        'sort_order',
        'template_path',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function scopeForCause(Builder $query, string $causeCode): Builder
    {
        return $query->where('termination_cause_code', $causeCode);
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
