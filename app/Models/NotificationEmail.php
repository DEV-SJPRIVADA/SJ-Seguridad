<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationEmail extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<NotificationType, $this>
     */
    public function notificationTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationType::class,
            'notification_type_email',
            'notification_email_id',
            'notification_type_id'
        );
    }
}
