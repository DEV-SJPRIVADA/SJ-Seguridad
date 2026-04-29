<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalRequisition extends Model
{
    use HasFactory;

    public const STATUS_SOLICITADA = 'solicitada';
    public const STATUS_EN_GESTION = 'en_gestion';
    public const STATUS_CONTRATADO = 'contratado';
    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'code',
        'requested_by',
        'managed_by',
        'request_date',
        'leader_name',
        'requesting_area_key',
        'position_id',
        'sex',
        'quantity',
        'replacement_document',
        'replacement_name',
        'operating_area_key',
        'request_reason_id',
        'client_id',
        'city_id',
        'client_type_id',
        'programming_type_id',
        'required_profile',
        'required_uniform',
        'cost_center',
        'requester_observation',
        'human_resources_observation',
        'status',
        'status_changed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'status_changed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SOLICITADA => 'Solicitada',
            self::STATUS_EN_GESTION => 'En gestion',
            self::STATUS_CONTRATADO => 'Contratado',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(RequisitionPosition::class);
    }

    public function requestReason(): BelongsTo
    {
        return $this->belongsTo(RequisitionRequestReason::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RequisitionClient::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(RequisitionCity::class);
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(RequisitionClientType::class);
    }

    public function programmingType(): BelongsTo
    {
        return $this->belongsTo(RequisitionProgrammingType::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PersonalRequisitionStatusLog::class);
    }
}
