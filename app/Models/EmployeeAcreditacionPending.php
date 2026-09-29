<?php

namespace App\Models;

use Database\Factories\EmployeeAcreditacionPendingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAcreditacionPending extends Model
{
    /** @use HasFactory<EmployeeAcreditacionPendingFactory> */
    use HasFactory;

    protected $table = 'employee_acreditacion_pending';

    public const STATUS_PENDING = 'pending';

    public const STATUS_OMITTED = 'omitted';

    public const STATUS_RESOLVED = 'resolved';

    public const RESOLVED_VIA_FIRST_ACREDITADO = 'first_acreditado';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_OMITTED,
        self::STATUS_RESOLVED,
    ];

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'full_name',
        'employee_ficha_profile_id',
        'personal_requisition_ficha_entry_id',
        'status',
        'enqueued_at',
        'enqueued_by',
        'omitted_at',
        'omitted_by',
        'omit_reason',
        'resolved_at',
        'resolved_by',
        'resolved_via',
        'acreditacion_acreditado_id',
    ];

    protected function casts(): array
    {
        return [
            'enqueued_at' => 'datetime',
            'omitted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function employeeFichaProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeFichaProfile::class, 'employee_ficha_profile_id');
    }

    public function fichaEntry(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisitionFichaEntry::class, 'personal_requisition_ficha_entry_id');
    }

    public function acreditacionAcreditado(): BelongsTo
    {
        return $this->belongsTo(AcreditacionAcreditado::class, 'acreditacion_acreditado_id');
    }

    public function enqueuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueued_by');
    }

    public function omittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'omitted_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForDocumentNumber(Builder $query, string $documentNumber): Builder
    {
        return $query->where('document_number', $documentNumber);
    }

    public function scopePendingWithActiveProfile(Builder $query): Builder
    {
        return $query
            ->pending()
            ->where(function (Builder $inner): void {
                $inner
                    ->whereHas('employeeFichaProfile', function (Builder $profile): void {
                        $profile->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO);
                    })
                    ->orWhere(function (Builder $byDocument): void {
                        $byDocument
                            ->whereNull('employee_ficha_profile_id')
                            ->whereExists(function ($sub): void {
                                $sub->selectRaw('1')
                                    ->from('employee_ficha_profiles')
                                    ->whereColumn(
                                        'employee_ficha_profiles.document_number',
                                        'employee_acreditacion_pending.document_number'
                                    )
                                    ->where(
                                        'employee_ficha_profiles.employment_status',
                                        EmployeeFichaProfile::STATUS_ACTIVO
                                    );
                            });
                    });
            });
    }
}
