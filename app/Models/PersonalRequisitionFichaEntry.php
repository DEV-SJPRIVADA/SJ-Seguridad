<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRequisitionFichaEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_requisition_id',
        'hired_document',
        'hired_full_name',
        'moved_to_ficha_at',
        'moved_to_ficha_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'moved_to_ficha_at' => 'datetime',
        ];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisition::class, 'personal_requisition_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_to_ficha_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('moved_to_ficha_at');
    }

    public function scopeInFicha(Builder $query): Builder
    {
        return $query->whereNotNull('moved_to_ficha_at');
    }

    public function requisitionCode(): ?string
    {
        return $this->requisition?->code;
    }

    public function positionName(): ?string
    {
        return $this->requisition?->position?->name;
    }

    public function clientName(): ?string
    {
        return $this->requisition?->client?->name;
    }

    public function cityName(): ?string
    {
        return $this->requisition?->city?->name;
    }
}
