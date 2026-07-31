<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function profile(): HasOne
    {
        return $this->hasOne(EmployeeFichaProfile::class, 'personal_requisition_ficha_entry_id');
    }

    public function scopeWithActiveProfile(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereDoesntHave('profile')
                ->orWhereHas('profile', fn (Builder $profile) => $profile->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO));
        });
    }

    public function scopeHireDateBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->where(function (Builder $inner) use ($from, $to): void {
            $inner->whereHas('profile', fn (Builder $profile) => $profile->whereBetween('hire_date', [$from, $to]))
                ->orWhere(function (Builder $withoutProfile) use ($from, $to): void {
                    $withoutProfile->whereDoesntHave('profile')
                        ->whereHas('requisition', fn (Builder $req) => $req->whereBetween('hiring_date', [$from, $to]));
                });
        });
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
