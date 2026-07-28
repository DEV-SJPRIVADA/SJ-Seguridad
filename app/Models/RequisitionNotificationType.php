<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RequisitionNotificationType extends Model
{
    public const SLUG_NEW_REQUISITION = 'new_requisition';

    public const SLUG_MANAGEMENT_APPROVAL_CARGO_NUEVO = 'management_approval_cargo_nuevo';

    protected $fillable = [
        'slug',
        'label',
        'description',
        'sort_order',
    ];

    /**
     * @return BelongsToMany<RequisitionNotificationEmail, $this>
     */
    public function notificationEmails(): BelongsToMany
    {
        return $this->belongsToMany(
            RequisitionNotificationEmail::class,
            'req_notif_type_email',
            'notification_type_id',
            'notification_email_id'
        );
    }
}
