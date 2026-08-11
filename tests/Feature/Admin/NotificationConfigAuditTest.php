<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\NotificationEmail;
use App\Models\NotificationType;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NotificationConfigAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_attach_email_creates_notification_config_audit_log(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.notifications');

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_NEW_REQUISITION)
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'Ops@Example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'admin',
            'area' => null,
            'event_type' => 'notification_config',
            'action' => 'email_attach',
            'user_id' => $user->id,
            'auditable_type' => NotificationType::class,
            'auditable_id' => $type->id,
        ]);

        $log = AuditLog::query()
            ->where('event_type', 'notification_config')
            ->where('action', 'email_attach')
            ->firstOrFail();

        $this->assertSame($type->module, $log->metadata['notification_module']);
        $this->assertSame($type->slug, $log->metadata['type_slug']);
        $this->assertSame($type->label, $log->metadata['type_label']);
        $this->assertSame('ops@example.com', $log->metadata['email']);
    }

    public function test_detach_email_creates_notification_config_audit_log(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.notifications');

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_NEW_REQUISITION)
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'ops@example.com',
            ])
            ->assertRedirect();

        $email = NotificationEmail::query()->where('name', 'ops@example.com')->firstOrFail();

        AuditLog::query()->delete();

        $this->actingAs($user)
            ->delete(route('admin.notifications.types.emails.detach', [$type, $email]))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'admin',
            'area' => null,
            'event_type' => 'notification_config',
            'action' => 'email_detach',
            'user_id' => $user->id,
            'auditable_type' => NotificationType::class,
            'auditable_id' => $type->id,
        ]);

        $log = AuditLog::query()
            ->where('event_type', 'notification_config')
            ->where('action', 'email_detach')
            ->firstOrFail();

        $this->assertSame($type->module, $log->metadata['notification_module']);
        $this->assertSame($type->slug, $log->metadata['type_slug']);
        $this->assertSame($type->label, $log->metadata['type_label']);
        $this->assertSame('ops@example.com', $log->metadata['email']);
    }

    public function test_duplicate_attach_does_not_create_additional_audit_log(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.notifications');

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_NEW_REQUISITION)
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'ops@example.com',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'ops@example.com',
            ])
            ->assertRedirect();

        $this->assertSame(
            1,
            AuditLog::query()
                ->where('event_type', 'notification_config')
                ->where('action', 'email_attach')
                ->count()
        );
    }

    public function test_audit_disabled_suppresses_notification_config_logs(): void
    {
        Config::set('audit.enabled', false);

        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.notifications');

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_NEW_REQUISITION)
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'ops@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
