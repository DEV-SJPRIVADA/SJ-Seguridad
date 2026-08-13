<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFichaEmploymentPeriod extends Model
{
    public const STATUS_ACTIVO = 'activo';

    public const STATUS_CERRADO = 'cerrado';

    protected $fillable = [
        'personal_requisition_ficha_entry_id',
        'personal_requisition_id',
        'sequence',
        'status',
        'hire_date',
        'salary',
        'position_code',
        'position_name',
        'cost_center_code',
        'cost_center_name',
        'contract_type_code',
        'contract_type_name',
        'contract_end_date',
        'work_center_name',
        'eps_code',
        'eps_name',
        'afp_code',
        'afp_name',
        'linkage_type',
        'termination_cause_code',
        'termination_cause_name',
        'is_rehireable',
        'last_work_day',
        'termination_date',
        'termination_notes',
        'termination_letter_type',
        'termination_letter_path',
        'opened_by',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'contract_end_date' => 'date',
            'last_work_day' => 'date',
            'termination_date' => 'date',
            'salary' => 'decimal:2',
            'is_rehireable' => 'boolean',
        ];
    }

    public function fichaEntry(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisitionFichaEntry::class, 'personal_requisition_ficha_entry_id');
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisition::class, 'personal_requisition_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVO);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CERRADO);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVO;
    }

    /**
     * @return array<string, mixed>
     */
    public function profileSnapshotAttributes(): array
    {
        return [
            'hire_date' => $this->hire_date,
            'salary' => $this->salary,
            'position_code' => $this->position_code,
            'position_name' => $this->position_name,
            'cost_center_code' => $this->cost_center_code,
            'cost_center_name' => $this->cost_center_name,
            'contract_type_code' => $this->contract_type_code,
            'contract_type_name' => $this->contract_type_name,
            'contract_end_date' => $this->contract_end_date,
            'work_center_name' => $this->work_center_name,
            'eps_code' => $this->eps_code,
            'eps_name' => $this->eps_name,
            'afp_code' => $this->afp_code,
            'afp_name' => $this->afp_name,
            'linkage_type' => $this->linkage_type,
            'employment_status' => $this->isActive()
                ? EmployeeFichaProfile::STATUS_ACTIVO
                : EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => $this->isActive() ? null : $this->termination_date,
        ];
    }
}
