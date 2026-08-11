<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemAuditDefaultDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_without_query_applies_default_thirty_day_filter(): void
    {
        PermissionCatalog::sync();

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(Permission::query()->pluck('name'));

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('super-admin');

        Carbon::setTestNow('2026-08-11 12:00:00');

        $recentLog = AuditLog::query()->create([
            'module' => 'admin',
            'event_type' => 'user_management',
            'action' => 'create',
            'reason' => 'Recent audit entry',
        ]);
        $recentLog->forceFill([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ])->save();

        $oldLog = AuditLog::query()->create([
            'module' => 'admin',
            'event_type' => 'user_management',
            'action' => 'update',
            'reason' => 'Old audit entry',
        ]);
        $oldLog->forceFill([
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ])->save();

        $expectedFrom = now()->subDays(30)->toDateString();
        $expectedTo = now()->toDateString();

        $response = $this->actingAs($user)
            ->get(route('admin.audit.index'));

        $response->assertOk()
            ->assertViewHas('dateFrom', $expectedFrom)
            ->assertViewHas('dateTo', $expectedTo)
            ->assertViewHas('logs', function ($logs): bool {
                return $logs->total() === 1
                    && $logs->first()->action === 'create';
            })
            ->assertSee('Recent audit entry')
            ->assertDontSee('Old audit entry');

        Carbon::setTestNow();
    }
}
