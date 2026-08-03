<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestMailLog extends Model
{
    public const STATUS_ENVIADO = 'enviado';

    public const STATUS_FALLIDO = 'fallido';

    public const TYPE_DIRECTOR_ASSIGNED = 'director_assigned';

    public const TYPE_REQUESTER_RESOLVED = 'requester_resolved';

    public const TYPE_COMPRAS_APPROVED = 'compras_approved';

    public const TYPE_REQUESTER_PROCESSED = 'requester_processed';

    protected $fillable = [
        'purchase_request_id',
        'mail_type',
        'recipient_email',
        'status',
        'detail',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_DIRECTOR_ASSIGNED => 'Asignacion a director',
            self::TYPE_REQUESTER_RESOLVED => 'Resolucion al solicitante',
            self::TYPE_COMPRAS_APPROVED => 'Aviso a compras',
            self::TYPE_REQUESTER_PROCESSED => 'Procesamiento al solicitante',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->mail_type] ?? $this->mail_type;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_FALLIDO => 'Fallido',
            default => $this->status,
        };
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
