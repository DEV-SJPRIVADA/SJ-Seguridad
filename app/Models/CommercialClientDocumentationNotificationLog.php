<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialClientDocumentationNotificationLog extends Model
{
    public const KIND_EXPIRING = 'expiring';

    public const KIND_EXPIRED = 'expired';

    protected $fillable = [
        'commercial_client_id',
        'documentation_expires_on',
        'alert_kind',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'documentation_expires_on' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommercialClient, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(CommercialClient::class, 'commercial_client_id');
    }
}
