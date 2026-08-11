<?php

namespace Tests\Feature\Admin;

use App\Mail\UserWelcomeMail;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminUserAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_store_logs_user_management_create_event(): void
    {
        Mail::fake();

        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Usuario Auditado',
            'document_number' => '9988776655',
            'email' => 'auditado@example.com',
            'role' => 'usuario',
            'is_active' => '1',
            'must_change_password' => '1',
            'permissions' => ['view.area.gestion_humana', 'view.board.gestion_humana.dashboard'],
        ])->assertRedirect(route('admin.users.index'));

        $createdUser = User::query()->where('email', 'auditado@example.com')->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'admin')
            ->where('event_type', 'user_management')
            ->where('action', 'create')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $createdUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertNull($log->area);
        $this->assertSame([
            'name' => 'Usuario Auditado',
            'email' => 'auditado@example.com',
            'document_number' => '9988776655',
            'area_key' => null,
            'sede_id' => null,
            'is_active' => true,
            'role' => 'usuario',
        ], $log->new_values);
        $this->assertSame(['permissions_count' => 2], $log->metadata);
        $this->assertAuditPayloadHasNoSecrets($log);

        Mail::assertSent(UserWelcomeMail::class);
    }

    public function test_update_logs_profile_changes_as_update_event(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser();
        $originalName = $user->name;
        $originalEmail = $user->email;
        $originalDocument = $user->document_number;

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com',
            'document_number' => '5544332211',
            'area_key' => 'operaciones',
        ]))->assertRedirect(route('admin.users.edit', $user));

        $log = $this->latestAuditLog('update', $user);

        $this->assertNotNull($log);
        $this->assertSame([
            'name' => $originalName,
            'email' => $originalEmail,
            'document_number' => $originalDocument,
            'area_key' => null,
        ], $log->old_values);
        $this->assertSame([
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com',
            'document_number' => '5544332211',
            'area_key' => 'operaciones',
        ], $log->new_values);
        $this->assertAuditPayloadHasNoSecrets($log);
    }

    public function test_update_logs_deactivate_event(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser(['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'is_active' => '0',
        ]))->assertRedirect(route('admin.users.edit', $user));

        $log = $this->latestAuditLog('deactivate', $user);

        $this->assertNotNull($log);
        $this->assertSame(['previous_is_active' => true], $log->metadata);
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_update_logs_activate_event(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser(['is_active' => false]);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'is_active' => '1',
        ]))->assertRedirect(route('admin.users.edit', $user));

        $log = $this->latestAuditLog('activate', $user);

        $this->assertNotNull($log);
        $this->assertSame(['previous_is_active' => false], $log->metadata);
    }

    public function test_update_logs_role_sync_event(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser();
        $user->syncRoles(['usuario']);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'role' => 'administrador',
        ]))->assertRedirect(route('admin.users.edit', $user));

        $log = $this->latestAuditLog('role_sync', $user);

        $this->assertNotNull($log);
        $this->assertSame(['role' => 'usuario'], $log->old_values);
        $this->assertSame(['role' => 'administrador'], $log->new_values);
    }

    public function test_update_logs_permissions_sync_with_diff_metadata(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser();
        $user->syncPermissions(['view.board.gestion_humana.dashboard']);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'permissions' => [
                'view.board.gestion_humana.dashboard',
                'view.area.gestion_humana',
            ],
        ]))->assertRedirect(route('admin.users.edit', $user));

        $log = $this->latestAuditLog('permissions_sync', $user);

        $this->assertNotNull($log);
        $this->assertSame(['view.area.gestion_humana'], $log->metadata['added']);
        $this->assertSame([], $log->metadata['removed']);
        $this->assertSame(1, $log->metadata['added_count']);
        $this->assertSame(0, $log->metadata['removed_count']);
    }

    public function test_update_logs_password_reset_without_secrets(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser(['must_change_password' => true]);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'password' => 'NuevaClaveSegura1!',
            'must_change_password' => '0',
        ]))->assertRedirect(route('admin.users.edit', $user));

        $passwordLog = $this->latestAuditLog('password_reset', $user);

        $this->assertNotNull($passwordLog);
        $this->assertSame(['admin_initiated' => true], $passwordLog->metadata);
        $this->assertAuditPayloadHasNoSecrets($passwordLog);

        $updateLog = $this->latestAuditLog('update', $user);
        $this->assertNotNull($updateLog);
        $this->assertArrayHasKey('must_change_password', $updateLog->old_values ?? []);
        $this->assertFalse($updateLog->new_values['must_change_password']);
    }

    public function test_update_can_emit_multiple_sub_events_in_one_save(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser(['is_active' => true]);
        $user->syncRoles(['usuario']);
        $user->syncPermissions(['view.board.gestion_humana.dashboard']);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'name' => 'Combo Audit',
            'role' => 'administrador',
            'is_active' => '0',
            'permissions' => ['view.area.gestion_humana'],
        ]))->assertRedirect(route('admin.users.edit', $user));

        $actions = AuditLog::query()
            ->where('module', 'admin')
            ->where('event_type', 'user_management')
            ->where('auditable_id', $user->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertContains('update', $actions);
        $this->assertContains('deactivate', $actions);
        $this->assertContains('role_sync', $actions);
        $this->assertContains('permissions_sync', $actions);
    }

    public function test_permissions_diff_metadata_is_capped_at_fifty_entries_total(): void
    {
        $admin = $this->adminUser();
        $user = $this->targetUser();

        $existing = $this->createPermissionNames('existing.perm.', 30);
        $incoming = $this->createPermissionNames('incoming.perm.', 30);
        $user->syncPermissions($existing);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), $this->baseUpdatePayload($user, [
            'permissions' => $incoming,
        ]))->assertRedirect(route('admin.users.edit', $user));

        $log = $this->latestAuditLog('permissions_sync', $user);

        $this->assertNotNull($log);
        $this->assertSame(30, $log->metadata['added_count']);
        $this->assertSame(30, $log->metadata['removed_count']);
        $this->assertCount(30, $log->metadata['added']);
        $this->assertCount(20, $log->metadata['removed']);
        $this->assertSame(50, count($log->metadata['added']) + count($log->metadata['removed']));
    }

    public function test_audit_disabled_does_not_log_user_management_events(): void
    {
        Mail::fake();
        Config::set('audit.enabled', false);

        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Sin Audit',
            'document_number' => '1122334455',
            'email' => 'sin-audit@example.com',
            'role' => 'usuario',
            'is_active' => '1',
            'permissions' => [],
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function adminUser(): User
    {
        $admin = User::query()
            ->where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))
            ->firstOrFail();
        $admin->update(['must_change_password' => false]);

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function targetUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'must_change_password' => false,
            'area_key' => null,
        ], $overrides));
        $user->assignRole('usuario');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function baseUpdatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'document_number' => $user->document_number,
            'email' => $user->email,
            'password' => '',
            'role' => 'usuario',
            'is_active' => $user->is_active ? '1' : '0',
            'must_change_password' => $user->must_change_password ? '1' : '0',
            'permissions' => $user->permissions->pluck('name')->all(),
        ], $overrides);
    }

    private function latestAuditLog(string $action, User $user): ?AuditLog
    {
        return AuditLog::query()
            ->where('module', 'admin')
            ->where('event_type', 'user_management')
            ->where('action', $action)
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();
    }

    private function assertAuditPayloadHasNoSecrets(AuditLog $log): void
    {
        foreach ([$log->old_values, $log->new_values, $log->metadata] as $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $this->assertArrayNotHasKey('password', $payload);
        }

        $encoded = json_encode([
            $log->old_values,
            $log->new_values,
            $log->metadata,
        ]);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('$2y$', $encoded);
    }

    /**
     * @return array<int, string>
     */
    private function createPermissionNames(string $prefix, int $count): array
    {
        $names = [];

        for ($index = 1; $index <= $count; $index++) {
            $name = $prefix.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            Permission::findOrCreate($name, 'web');
            $names[] = $name;
        }

        return $names;
    }
}
