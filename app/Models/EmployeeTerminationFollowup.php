<?php

namespace App\Models;

use Database\Factories\EmployeeTerminationFollowupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTerminationFollowup extends Model
{
    /** @use HasFactory<EmployeeTerminationFollowupFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const CHECK_FIELDS = [
        'check_orden_examenes',
        'check_enviado',
        'check_control_roll',
        'check_retiro_arl',
        'check_retiro_cesantias',
        'check_recibido',
        'check_paz_y_salvo',
        'check_reporte_noved',
    ];

    /**
     * Labels UI operativos (mayúsculas).
     *
     * @var array<string, string>
     */
    public const CHECK_LABELS = [
        'check_orden_examenes' => 'EXAMENES',
        'check_enviado' => 'ENVIADO',
        'check_control_roll' => 'CONTROL ROLL',
        'check_retiro_arl' => 'RETIRO ARL',
        'check_retiro_cesantias' => 'RET. CESANT',
        'check_recibido' => 'RECIBIDO',
        'check_paz_y_salvo' => 'PAZ Y SALVO',
        'check_reporte_noved' => 'REP. NOVED',
    ];

    protected $fillable = [
        'personal_requisition_ficha_entry_id',
        'employee_ficha_employment_period_id',
        'document_number',
        'full_name',
        'position_name',
        'termination_cause_code',
        'termination_cause_name',
        'is_rehireable',
        'termination_notes',
        'termination_date',
        'registered_at',
        'letter_generated',
        'check_orden_examenes',
        'check_enviado',
        'check_control_roll',
        'check_retiro_arl',
        'check_retiro_cesantias',
        'check_recibido',
        'check_paz_y_salvo',
        'check_reporte_noved',
        'payroll_delivered_at',
        'created_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'letter_generated' => false,
        'check_orden_examenes' => false,
        'check_enviado' => false,
        'check_control_roll' => false,
        'check_retiro_arl' => false,
        'check_retiro_cesantias' => false,
        'check_recibido' => false,
        'check_paz_y_salvo' => false,
        'check_reporte_noved' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_rehireable' => 'boolean',
            'termination_date' => 'date',
            'registered_at' => 'datetime',
            'letter_generated' => 'boolean',
            'check_orden_examenes' => 'boolean',
            'check_enviado' => 'boolean',
            'check_control_roll' => 'boolean',
            'check_retiro_arl' => 'boolean',
            'check_retiro_cesantias' => 'boolean',
            'check_recibido' => 'boolean',
            'check_paz_y_salvo' => 'boolean',
            'check_reporte_noved' => 'boolean',
            'payroll_delivered_at' => 'date',
        ];
    }

    public function fichaEntry(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisitionFichaEntry::class, 'personal_requisition_ficha_entry_id');
    }

    public function employmentPeriod(): BelongsTo
    {
        return $this->belongsTo(EmployeeFichaEmploymentPeriod::class, 'employee_ficha_employment_period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOkTodo(): bool
    {
        foreach (self::CHECK_FIELDS as $field) {
            if (! $this->{$field}) {
                return false;
            }
        }

        return true;
    }

    /**
     * OK TODO calculado (AND de los 8 checks). No es columna de BD.
     */
    protected function okTodo(): Attribute
    {
        return Attribute::get(fn (): bool => $this->isOkTodo());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        $term = trim((string) $q);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner->where('document_number', 'like', $like)
                ->orWhere('full_name', 'like', $like);
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOkTodo(Builder $query): Builder
    {
        foreach (self::CHECK_FIELDS as $field) {
            $query->where($field, true);
        }

        return $query;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeIncompletos(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            foreach (self::CHECK_FIELDS as $field) {
                $inner->orWhere($field, false);
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSinCarta(Builder $query): Builder
    {
        return $query->where('letter_generated', false);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'incompletos' => $query->incompletos(),
            'ok_todo' => $query->okTodo(),
            'sin_carta' => $query->sinCarta(),
            default => $query,
        };
    }
}
