<?php

namespace Tests\Feature;

use App\Jobs\WriteAuditLogJob;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\SystemAuditService;
use App\Services\Indicadores\AuditLogService;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_write_persists_audit_log(): void
    {
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);

        $user = User::factory()->create();
        $this->actingAs($user);

        app(SystemAuditService::class)->logEvent(
            module: 'admin',
            eventType: 'user_management',
            action: 'create',
            reason: 'Test audit entry',
        );

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'admin',
            'event_type' => 'user_management',
            'action' => 'create',
            'user_id' => $user->id,
            'reason' => 'Test audit entry',
        ]);
    }

    public function test_audit_disabled_is_no_op(): void
    {
        Config::set('audit.enabled', false);
        Config::set('audit.queue', false);

        $user = User::factory()->create();
        $this->actingAs($user);

        app(SystemAuditService::class)->logEvent(
            module: 'admin',
            eventType: 'user_management',
            action: 'create',
        );

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_queued_write_dispatches_job_when_audit_queue_enabled(): void
    {
        Config::set('audit.enabled', true);
        Config::set('audit.queue', true);

        Bus::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        app(SystemAuditService::class)->logEvent(
            module: 'admin',
            eventType: 'user_management',
            action: 'update',
        );

        Bus::assertDispatched(WriteAuditLogJob::class, function (WriteAuditLogJob $job): bool {
            return $job->payload['module'] === 'admin'
                && $job->payload['event_type'] === 'user_management'
                && $job->payload['action'] === 'update';
        });

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_super_admin_with_permission_can_access_global_audit(): void
    {
        PermissionCatalog::sync();

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(Permission::query()->pluck('name'));

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('super-admin');

        AuditLog::query()->create([
            'module' => 'indicadores',
            'area' => 'operaciones',
            'event_type' => 'export',
            'action' => 'consolidado_pdf',
            'reason' => 'Export test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('Auditoria del sistema')
            ->assertSee('consolidado_pdf');
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        PermissionCatalog::sync();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('view.dashboard');

        $this->actingAs($user)
            ->get(route('admin.audit.index'))
            ->assertForbidden();
    }

    public function test_indicadores_audit_wrapper_writes_module_indicadores(): void
    {
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);

        $user = User::factory()->create();
        $this->actingAs($user);

        app(AuditLogService::class)->logEvent(
            eventType: 'admin_action',
            action: 'dashboard_view',
            reason: 'Wrapper test',
        );

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'indicadores',
            'area' => 'operaciones',
            'event_type' => 'admin_action',
            'action' => 'dashboard_view',
            'reason' => 'Wrapper test',
        ]);
    }

    public function test_audit_purge_dry_run_reports_without_deleting(): void
    {
        Config::set('audit.retention_months', 1);

        $log = AuditLog::query()->create([
            'module' => 'admin',
            'event_type' => 'test',
            'action' => 'old',
        ]);

        $log->forceFill([
            'created_at' => now()->subMonths(3),
            'updated_at' => now()->subMonths(3),
        ])->save();

        $this->artisan('audit:purge --dry-run --force')
            ->expectsOutputToContain('Eligible rows: 1')
            ->assertSuccessful();

        $this->assertDatabaseCount('audit_logs', 1);
    }
}
