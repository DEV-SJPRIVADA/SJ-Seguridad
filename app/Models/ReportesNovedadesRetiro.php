<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportesNovedadesRetiro extends Model
{
    use SoftDeletes;

    protected $table = 'reportes_novedades_retiros';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'employee_name',
        'fecha_ingreso',
        'tipo',
        'cargo',
        'destino',
        'novedad',
        'fecha_retiro',
        'motivo_retiro',
        'observaciones',
        'employee_termination_followup_id',
        'personal_requisition_ficha_entry_id',
        'observacion_nomina',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'novedad' => 'RETIRO',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_retiro' => 'date',
            'employee_termination_followup_id' => 'integer',
            'personal_requisition_ficha_entry_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function terminationFollowup(): BelongsTo
    {
        return $this->belongsTo(EmployeeTerminationFollowup::class, 'employee_termination_followup_id');
    }

    public function fichaEntry(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisitionFichaEntry::class, 'personal_requisition_ficha_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
