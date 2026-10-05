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

    /**
     * Campos de fecha permitidos para el filtro de rango Desde/Hasta.
     *
     * @var array<string, string>
     */
    public const DATE_FILTER_FIELDS = [
        'registered_at' => 'FECHA DE REGISTRO',
        'termination_date' => 'FECHA DESVINCULACION',
        'payroll_delivered_at' => 'FECHA ENTREGADO NOMINA',
    ];

    public const DEFAULT_DATE_FILTER_FIELD = 'payroll_delivered_at';

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
     * Completo para filtros: los 8 checks en verdadero y con FECHA ENTREGADO NOMINA.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOkTodo(Builder $query): Builder
    {
        foreach (self::CHECK_FIELDS as $field) {
            $query->where($field, true);
        }

        return $query->whereNotNull('payroll_delivered_at');
    }

    /**
     * Incompleto: OK TODO en No (falta algún check) o sin FECHA ENTREGADO NOMINA.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeIncompletos(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            foreach (self::CHECK_FIELDS as $field) {
                $inner->orWhere($field, false);
            }
            $inner->orWhereNull('payroll_delivered_at');
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

    /**
     * Filtra por recontratable: `1`/`si` = Sí, `0`/`no` = No; vacío = sin filtro.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRehireableFilter(Builder $query, string $value): Builder
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'si', 'sí', 'true' => $query->where('is_rehireable', true),
            '0', 'no', 'false' => $query->where('is_rehireable', false),
            default => $query,
        };
    }

    /**
     * Filtra por rango de fecha sobre la columna indicada (`registered_at`, `termination_date` o `payroll_delivered_at`).
     * Extremos opcionales; si ambos vienen invertidos se intercambian.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDateFieldBetween(Builder $query, string $column, ?string $from, ?string $to): Builder
    {
        if (! array_key_exists($column, self::DATE_FILTER_FIELDS)) {
            $column = self::DEFAULT_DATE_FILTER_FIELD;
        }

        if ($from === null && $to === null) {
            return $query;
        }

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from !== null) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePayrollDeliveredBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query->dateFieldBetween('payroll_delivered_at', $from, $to);
    }
}
