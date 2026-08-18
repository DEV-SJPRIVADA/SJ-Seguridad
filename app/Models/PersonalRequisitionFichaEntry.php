<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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

    public function employmentPeriods(): HasMany
    {
        return $this->hasMany(EmployeeFichaEmploymentPeriod::class, 'personal_requisition_ficha_entry_id');
    }

    public function activeEmploymentPeriod(): HasOne
    {
        return $this->hasOne(EmployeeFichaEmploymentPeriod::class, 'personal_requisition_ficha_entry_id')
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO);
    }

    public function isRehirePending(): bool
    {
        return $this->moved_to_ficha_at === null
            && $this->profile?->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO;
    }

    public function rehireableLabel(): ?string
    {
        if ($this->employmentStatus() !== EmployeeFichaProfile::STATUS_DESVINCULADO) {
            return null;
        }

        $latestClosed = $this->relationLoaded('employmentPeriods')
            ? $this->employmentPeriods
                ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
                ->sortByDesc('sequence')
                ->first()
            : $this->employmentPeriods()
                ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
                ->orderByDesc('sequence')
                ->first();

        if ($latestClosed === null) {
            return null;
        }

        return $latestClosed->is_rehireable ? 'Si' : 'No';
    }

    public function employmentPeriodsCount(): int
    {
        return $this->employmentPeriods()->count();
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
        if ($this->profile?->position_name) {
            return $this->profile->position_name;
        }

        return $this->requisition?->position?->name;
    }

    public function clientName(): ?string
    {
        if ($this->profile?->work_center_name) {
            return $this->profile->work_center_name;
        }

        return $this->requisition?->client?->name;
    }

    public function cityName(): ?string
    {
        if ($this->profile?->residence_city_name) {
            return $this->profile->residence_city_name;
        }

        return $this->requisition?->city?->name;
    }

    public function isManualEntry(): bool
    {
        return $this->personal_requisition_id === null;
    }

    public function hireDate(): ?Carbon
    {
        return $this->profile?->hire_date ?? $this->requisition?->hiring_date;
    }

    public function contractDate(): ?Carbon
    {
        return $this->requisition?->hiring_date;
    }

    public function terminationDate(): ?Carbon
    {
        return $this->profile?->termination_date;
    }

    public function employmentStatus(): ?string
    {
        if ($this->moved_to_ficha_at === null) {
            return null;
        }

        return $this->profile?->employment_status ?? EmployeeFichaProfile::STATUS_ACTIVO;
    }

    public function employmentStatusLabel(): ?string
    {
        $status = $this->employmentStatus();

        if ($status === null) {
            return null;
        }

        /** @var array<string, string> $labels */
        $labels = config('employee_ficha.employment_status', []);

        return $labels[$status] ?? $status;
    }

    public function scopeWithEmploymentStatus(Builder $query, string $status): Builder
    {
        if ($status === EmployeeFichaProfile::STATUS_ACTIVO) {
            return $query->where(function (Builder $inner): void {
                $inner->whereDoesntHave('profile')
                    ->orWhereHas('profile', fn (Builder $profile) => $profile->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO));
            });
        }

        return $query->whereHas('profile', fn (Builder $profile) => $profile->where('employment_status', $status));
    }
}
