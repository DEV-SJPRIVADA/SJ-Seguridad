<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSyncState extends Model
{
    protected $table = 'integration_sync_state';

    protected $fillable = [
        'key',
        'last_watermark',
        'last_run_at',
        'last_status',
        'last_sent',
        'last_failed',
    ];

    protected function casts(): array
    {
        return [
            'last_watermark' => 'datetime',
            'last_run_at' => 'datetime',
            'last_sent' => 'integer',
            'last_failed' => 'integer',
        ];
    }
}
