<?php

namespace Tests\Feature;

use App\Models\NotificationEmail;
use App\Models\NotificationType;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_admin_forbidden_without_permission(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('view.dashboard');

        $this->actingAs($user)
            ->get(route('admin.notifications.index'))
            ->assertForbidden();
    }

    public function test_notification_admin_lists_only_configurable_types(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.notifications');

        $this->actingAs($user)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Configuracion de notificaciones')
            ->assertSee('Nueva requisicion')
            ->assertDontSee('Autorizacion requisicion cargo nuevo')
            ->assertDontSee('Correos destinatarios');
    }

    public function test_add_and_remove_email_on_notification_type(): void
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
            ->assertRedirect(route('admin.notifications.index'));

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'ops@example.com',
            ])
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertSame(1, $type->fresh()->notificationEmails()->count());

        $email = NotificationEmail::query()->where('name', 'ops@example.com')->firstOrFail();
        $this->assertTrue($type->fresh()->notificationEmails()->where('notification_emails.id', $email->id)->exists());

        $this->actingAs($user)
            ->delete(route('admin.notifications.types.emails.detach', [$type, $email]))
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertFalse($type->fresh()->notificationEmails()->where('notification_emails.id', $email->id)->exists());
        $this->assertNull(NotificationEmail::query()->find($email->id));
    }

    public function test_cannot_attach_email_to_non_configurable_type(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.notifications');

        $type = NotificationType::query()
            ->where('slug', NotificationType::SLUG_MANAGEMENT_APPROVAL_CARGO_NUEVO)
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.notifications.types.emails.attach', $type), [
                'email' => 'gerencia@example.com',
            ])
            ->assertNotFound();
    }

    public function test_parameters_excludes_notification_sections(): void
    {
        PermissionCatalog::sync();

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $manager->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.gestion_humana.requisiciones',
        ]);

        $this->actingAs($manager)
            ->get(route('requisitions.parameters', ['module' => 'gestion_humana']))
            ->assertOk()
            ->assertDontSee('Tipos de notificacion')
            ->assertDontSee('Correos de notificacion');

        $this->actingAs($manager)
            ->post(route('requisitions.parameters.store', ['module' => 'gestion_humana', 'type' => 'emails']), [
                'name' => 'x@example.com',
            ])
            ->assertNotFound();
    }
}
