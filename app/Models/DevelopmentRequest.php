<?php

namespace App\Models;

use Database\Factories\DevelopmentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevelopmentRequest extends Model
{
    /** @use HasFactory<DevelopmentRequestFactory> */
    use HasFactory;

    public const STATUS_BORRADOR = 'borrador';

    public const STATUS_PENDIENTE_APROBACION_LIDER = 'pendiente_aprobacion_lider';

    public const STATUS_RADICADO = 'radicado';

    public const STATUS_DEVUELTO = 'devuelto';

    public const STATUS_EN_ANALISIS = 'en_analisis';

    public const STATUS_EN_DESARROLLO = 'en_desarrollo';

    public const STATUS_EN_PRUEBAS = 'en_pruebas';

    public const STATUS_ENTREGADO = 'entregado';

    public const STATUS_CERRADO = 'cerrado';

    public const STATUS_RECHAZADO = 'rechazado';

    public const TYPE_NUEVO = 'nuevo';

    public const TYPE_MEJORA = 'mejora';

    public const TYPE_CORRECCION = 'correccion';

    public const TYPE_AUTOMATIZACION = 'automatizacion';

    public const TYPE_REPORTE = 'reporte';

    public const PRIORITY_URGENTE = 'urgente';

    public const PRIORITY_IMPORTANTE = 'importante';

    public const PRIORITY_MEJORA = 'mejora';

    public const PRIORITY_SOPORTE = 'soporte';

    protected $attributes = [
        'status' => self::STATUS_BORRADOR,
        'requires_fo_ge_12' => false,
        'requires_extended_analysis' => false,
    ];

    protected $fillable = [
        'code',
        'area_key',
        'status',
        'request_type',
        'title',
        'associated_norm',
        'proceso_sede',
        'requester_name',
        'requester_position',
        'requester_email',
        'requester_phone',
        'description',
        'current_process_problem',
        'desired_steps',
        'users_description',
        'restrictions',
        'scope_in',
        'scope_out',
        'acceptance_criteria',
        'permissions_matrix',
        'reports',
        'suggested_priority',
        'desired_date',
        'desired_date_justification',
        'notes',
        'created_by',
        'leader_id',
        'assigned_programmer_id',
        'submitted_at',
        'leader_decided_at',
        'leader_decision_notes',
        'radicated_at',
        'closed_at',
        'tic_viability',
        'tic_confirmed_priority',
        'tic_complexity',
        'requires_fo_ge_12',
        'requires_extended_analysis',
        'tic_estimated_date',
        'tic_risks',
        'tic_analysis_notes',
        'uat_result',
        'uat_notes',
        'closure_notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'permissions_matrix' => 'array',
            'desired_date' => 'date',
            'submitted_at' => 'datetime',
            'leader_decided_at' => 'datetime',
            'radicated_at' => 'datetime',
            'closed_at' => 'datetime',
            'requires_fo_ge_12' => 'boolean',
            'requires_extended_analysis' => 'boolean',
            'tic_estimated_date' => 'date',
        ];
    }

    /** @return array<string, string> */
    public static function estadosLabels(): array
    {
        return [
            self::STATUS_BORRADOR => 'Borrador',
            self::STATUS_PENDIENTE_APROBACION_LIDER => 'Pendiente aprobacion lider',
            self::STATUS_RADICADO => 'Radicado',
            self::STATUS_DEVUELTO => 'Devuelto',
            self::STATUS_EN_ANALISIS => 'En analisis',
            self::STATUS_EN_DESARROLLO => 'En desarrollo',
            self::STATUS_EN_PRUEBAS => 'En pruebas',
            self::STATUS_ENTREGADO => 'Entregado',
            self::STATUS_CERRADO => 'Cerrado',
            self::STATUS_RECHAZADO => 'Rechazado',
        ];
    }

    /** @return array<string, string> */
    public static function tiposLabels(): array
    {
        return [
            self::TYPE_NUEVO => 'Nuevo',
            self::TYPE_MEJORA => 'Mejora',
            self::TYPE_CORRECCION => 'Correccion',
            self::TYPE_AUTOMATIZACION => 'Automatizacion',
            self::TYPE_REPORTE => 'Reporte',
        ];
    }

    /** @return array<string, string> */
    public static function prioridadesLabels(): array
    {
        return [
            self::PRIORITY_URGENTE => 'Urgente',
            self::PRIORITY_IMPORTANTE => 'Importante',
            self::PRIORITY_MEJORA => 'Mejora',
            self::PRIORITY_SOPORTE => 'Soporte',
        ];
    }

    public function estadoLabel(): string
    {
        return self::estadosLabels()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function tipoLabel(): string
    {
        return self::tiposLabels()[$this->request_type] ?? (string) $this->request_type;
    }

    public function prioridadLabel(): string
    {
        return self::prioridadesLabels()[$this->suggested_priority] ?? (string) $this->suggested_priority;
    }

    public function areaLabel(): ?string
    {
        return config("access.areas.{$this->area_key}");
    }

    public function isConversationClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CERRADO, self::STATUS_RECHAZADO], true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function assignedProgrammer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_programmer_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DevelopmentRequestAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(DevelopmentRequestStatusLog::class)->oldest('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DevelopmentRequestMessage::class)->oldest('id');
    }
}
