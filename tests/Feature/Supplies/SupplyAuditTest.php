<?php

namespace Tests\Feature\Supplies;

use App\Models\AuditLog;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupplyAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
        Mail::fake();
    }

    public function test_store_writes_create_audit_event_with_dynamic_area(): void
    {
        $user = $this->requester('operaciones');
        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($user)->post(route('supplies.store', ['module' => 'operaciones']), [
            'items' => [
                [
                    'type' => 'catalog',
                    'product_id' => $product->id,
                    'current_inventory' => 2,
                    'quantity' => 5,
                ],
            ],
        ])->assertRedirect();

        $supplyRequest = SupplyRequest::query()->latest()->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'supplies')
            ->where('event_type', 'supply_request')
            ->where('action', 'create')
            ->where('auditable_id', $supplyRequest->id)
            ->firstOrFail();

        $this->assertSame('operaciones', $log->area);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('operaciones', $log->metadata['area_key']);
        $this->assertSame(1, $log->metadata['items_count']);
        $this->assertSame($user->sede_id, $log->metadata['sede_id']);
    }

    public function test_approval_update_approve_writes_quality_approve_audit(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('operaciones');
        $request = $this->supplyRequest($owner, 'operaciones');
        $item = $request->items()->firstOrFail();

        $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'items' => [
                $item->id => ['approved_quantity' => 6],
            ],
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'supplies')
            ->where('event_type', 'supply_request')
            ->where('action', 'quality_approve')
            ->where('auditable_id', $request->id)
            ->firstOrFail();

        $this->assertSame('operaciones', $log->area);
        $this->assertSame(['status' => 'pendiente_calidad'], $log->old_values);
        $this->assertSame(['status' => 'aprobada_calidad'], $log->new_values);
        $this->assertSame(1, $log->metadata['items_approved_count']);
    }

    public function test_product_update_deactivate_writes_deactivate_audit(): void
    {
        $manager = $this->catalogManager('calidad');

        $product = SupplyProduct::query()->create([
            'name' => 'Producto Audit Test',
            'category' => 'Aseo',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->patch(route('supplies.products.update', [
            'module' => 'calidad',
            'product' => $product->id,
        ]), [
            'name' => 'Producto Audit Test',
            'description' => null,
            'category' => 'Aseo',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'supplies',
            'event_type' => 'supply_product',
            'action' => 'update',
            'auditable_id' => $product->id,
        ]);

        $log = AuditLog::query()
            ->where('module', 'supplies')
            ->where('event_type', 'supply_product')
            ->where('action', 'deactivate')
            ->where('auditable_id', $product->id)
            ->firstOrFail();

        $this->assertSame('calidad', $log->area);
        $this->assertTrue($log->metadata['previous_is_active']);
    }

    public function test_catalog_export_writes_catalog_excel_audit(): void
    {
        $manager = $this->catalogManager('operaciones');
        $expectedCount = SupplyProduct::query()->count();

        $this->actingAs($manager)->get(route('supplies.products.export', [
            'module' => 'operaciones',
        ]))->assertOk();

        $log = AuditLog::query()
            ->where('module', 'supplies')
            ->where('event_type', 'export')
            ->where('action', 'catalog_excel')
            ->firstOrFail();

        $this->assertSame('operaciones', $log->area);
        $this->assertSame($expectedCount, $log->metadata['row_count']);
    }

    private function requester(string $areaKey, ?SupplySite $site = null): User
    {
        $site ??= SupplySite::query()->where('name', 'cali_central')->firstOrFail();

        $user = User::factory()->create([
            'area_key' => $areaKey,
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        return $user;
    }

    private function qualityReviewer(string $areaKey): User
    {
        $user = $this->requester($areaKey);
        $user->givePermissionTo('view.board.'.$areaKey.'.suministros');
        $user->givePermissionTo('supply.tab.quality');

        return $user;
    }

    private function catalogManager(string $areaKey): User
    {
        $user = User::factory()->create([
            'area_key' => $areaKey,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.'.$areaKey.'.suministros',
            'supply.tab.catalog',
        ]);

        return $user;
    }

    private function supplyRequest(User $user, string $areaKey, string $status = 'pendiente_calidad'): SupplyRequest
    {
        $site = $user->site ?? SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();

        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $areaKey,
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => $status,
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'current_inventory' => 1,
            'requested_quantity' => 8,
            'is_not_in_catalog' => false,
        ]);

        return $request;
    }
}
