<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RequisitionNotificationEmail extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'sort_order'];

    /**
     * @return BelongsToMany<RequisitionNotificationType, $this>
     */
    public function notificationTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            RequisitionNotificationType::class,
            'req_notif_type_email',
            'notification_email_id',
            'notification_type_id'
        );
    }
}
