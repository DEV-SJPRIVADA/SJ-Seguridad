<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationType extends Model
{
    public const MODULE_REQUISITIONS = 'requisitions';

    public const SLUG_NEW_REQUISITION = 'new_requisition';

    public const SLUG_MANAGEMENT_APPROVAL_CARGO_NUEVO = 'management_approval_cargo_nuevo';

    protected $fillable = [
        'module',
        'slug',
        'label',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<NotificationType>  $query
     * @return Builder<NotificationType>
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * @return BelongsToMany<NotificationEmail, $this>
     */
    public function notificationEmails(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationEmail::class,
            'notification_type_email',
            'notification_type_id',
            'notification_email_id'
        );
    }
}
