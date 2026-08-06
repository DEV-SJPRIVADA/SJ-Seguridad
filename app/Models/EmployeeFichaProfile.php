<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFichaProfile extends Model
{
    public const STATUS_ACTIVO = 'activo';

    public const STATUS_DESVINCULADO = 'desvinculado';

    protected $fillable = [
        'personal_requisition_ficha_entry_id',
        'document_number',
        'full_name',
        'first_surname',
        'second_surname',
        'first_name',
        'second_name',
        'document_type',
        'birth_date',
        'age',
        'expedition_city_code',
        'expedition_city_name',
        'expedition_date',
        'residence_city_code',
        'residence_city_name',
        'address',
        'phone',
        'phone_secondary',
        'blood_type',
        'sex',
        'salary',
        'education_level',
        'marital_status',
        'children_count',
        'email',
        'linkage_type',
        'contributor_type',
        'hire_date',
        'contract_end_date',
        'termination_date',
        'employment_status',
        'work_center_name',
        'cost_center_code',
        'cost_center_name',
        'position_code',
        'position_name',
        'salary_scale',
        'salary_type_code',
        'salary_type_name',
        'contract_type_code',
        'contract_type_name',
        'eps_code',
        'eps_name',
        'afp_code',
        'afp_name',
        'arp_name',
        'risk_level',
        'compensation_fund_name',
        'bank_code',
        'bank_name',
        'account_type',
        'account_number',
        'payment_method_code',
        'economic_activity_code',
        'economic_activity_name',
        'payroll_extra',
        'archive_shelf',
        'archive_box',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'expedition_date' => 'date',
            'hire_date' => 'date',
            'contract_end_date' => 'date',
            'termination_date' => 'date',
            'salary' => 'decimal:2',
            'payroll_extra' => 'array',
        ];
    }

    public function fichaEntry(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisitionFichaEntry::class, 'personal_requisition_ficha_entry_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('employment_status', self::STATUS_ACTIVO);
    }

    public function syncEmploymentStatusFromTerminationDate(): void
    {
        if ($this->termination_date !== null && $this->termination_date->lte(now()->startOfDay())) {
            $this->employment_status = self::STATUS_DESVINCULADO;

            return;
        }

        $this->employment_status = self::STATUS_ACTIVO;
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollExtraValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->payroll_extra, $key, $default);
    }
}
