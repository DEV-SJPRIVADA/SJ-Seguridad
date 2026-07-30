<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialServiceContractNotificationLog extends Model
{
    protected $fillable = [
        'commercial_service_id',
        'contract_end',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'contract_end' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommercialService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(CommercialService::class, 'commercial_service_id');
    }
}
