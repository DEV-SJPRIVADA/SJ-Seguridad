<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSyncLog extends Model
{
    protected $table = 'integration_sync_log';

    protected $fillable = [
        'mode',
        'started_at',
        'finished_at',
        'status',
        'records_sent',
        'records_failed',
        'batches',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'records_sent' => 'integer',
            'records_failed' => 'integer',
            'batches' => 'integer',
        ];
    }
}
