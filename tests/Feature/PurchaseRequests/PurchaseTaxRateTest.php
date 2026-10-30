<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\PurchaseTaxRate;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IVA por tasa del catálogo: selector en Emitir orden, cálculo sobre cantidad × valor unidad y catálogo en Catálogos → IVA.
 */
class PurchaseTaxRateTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $this->compras->givePermissionTo('purchase.tab.processing');
    }

    public function test_default_catalog_has_0_5_and_19_percent(): void
    {
        $this->assertSame(
            [0.0, 5.0, 19.0],
            PurchaseTaxRate::query()->activas()->pluck('porcentaje')->map(fn ($p): float => (float) $p)->all(),
        );
    }

    public function test_order_form_shows_tax_rate_selector_with_19_by_default(): void
    {
        $purchase = $this->approvedPurchase();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.purchase.create', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertOk()
            ->assertSee('name="items[0][iva_porcentaje]"', false)
            ->assertSee('value="19"', false)
            ->assertSee('19% — IVA 19%')
            ->assertSee('Valor IVA')
            ->assertDontSee('name="items[0][iva]"', false);
    }

    public function test_iva_is_calculated_from_the_selected_rate(): void
    {
        $purchase = $this->approvedPurchase();
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), [
            'provider_id' => $provider->id,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Cálculo de IVA por tasa.',
            'items' => [[
                'cantidad' => 2,
                'descripcion' => 'CAMARA',
                'valor_unidad' => 100,
                'iva_porcentaje' => '19',
                // Un valor de IVA manipulado se ignora cuando llega la tasa.
                'iva' => 999,
            ]],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->latest('id')->firstOrFail();
        $line = $order->items->first();

        $this->assertEquals(38.0, (float) $line->iva);
        $this->assertEquals(19.0, (float) $line->iva_porcentaje);
        $this->assertEquals(238.0, (float) $line->valor_total);
        $this->assertEquals(238.0, (float) $order->total);
        $this->assertSame('38.00 (19%)', $line->ivaImpreso());

        $html = app(PurchaseOrderPdfExporter::class)->html($order);
        $this->assertStringContainsString('38.00 (19%)', $html);
    }

    public function test_inactive_or_unknown_rate_is_rejected(): void
    {
        PurchaseTaxRate::query()->where('porcentaje', 5)->update(['activo' => false]);
        $purchase = $this->approvedPurchase();
        $provider = PurchaseProvider::factory()->create();

        foreach (['5', '12'] as $rate) {
            $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
                'module' => 'compras',
                'purchase_request' => $purchase->id,
            ]), [
                'provider_id' => $provider->id,
                'fecha' => now()->toDateString(),
                'observaciones' => 'Tasa inactiva rechazada.',
                'items' => [[
                    'cantidad' => 1,
                    'descripcion' => 'CAMARA',
                    'valor_unidad' => 100,
                    'iva_porcentaje' => $rate,
                ]],
            ])->assertSessionHasErrors('items.0.iva_porcentaje');
        }

        $this->assertSame(0, PurchaseOrder::query()->count());
    }

    public function test_catalog_tab_uses_provider_permissions(): void
    {
        $viewer = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $viewer->givePermissionTo('purchase.providers.view');

        $this->actingAs($viewer)
            ->get(route('purchase-requests.tax-rates.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('Tasas de IVA')
            ->assertSee('19%')
            ->assertDontSee('Nueva tasa');

        $this->actingAs($viewer)
            ->post(route('purchase-requests.tax-rates.store', ['module' => 'compras']), ['nombre' => 'X', 'porcentaje' => 8])
            ->assertForbidden();

        $editor = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $editor->givePermissionTo('purchase.providers.edit');

        $this->actingAs($editor)
            ->post(route('purchase-requests.tax-rates.store', ['module' => 'compras']), [
                'nombre' => 'iva reducido',
                'porcentaje' => 8,
                'activo' => '1',
            ])
            ->assertRedirect(route('purchase-requests.tax-rates.index', ['module' => 'compras']));

        $rate = PurchaseTaxRate::query()->where('porcentaje', 8)->firstOrFail();
        $this->assertSame('IVA REDUCIDO', $rate->nombre);
        $this->assertTrue($rate->activo);

        // Porcentaje duplicado.
        $this->actingAs($editor)
            ->post(route('purchase-requests.tax-rates.store', ['module' => 'compras']), ['nombre' => 'Otro 19', 'porcentaje' => 19])
            ->assertSessionHasErrors('porcentaje');

        // Inactivar: deja de aparecer al emitir.
        $this->actingAs($editor)
            ->patch(route('purchase-requests.tax-rates.update', ['module' => 'compras', 'purchase_tax_rate' => $rate->id]), [
                'nombre' => 'IVA REDUCIDO',
                'porcentaje' => 8,
            ])
            ->assertRedirect();

        $this->assertFalse($rate->fresh()->activo);
        $this->assertNotContains(8.0, PurchaseTaxRate::query()->activas()->pluck('porcentaje')->map(fn ($p): float => (float) $p)->all());
    }

    private function approvedPurchase(): PurchaseRequest
    {
        $requester = User::factory()->create(['area_key' => 'operaciones']);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => 9301,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $requester->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);
        $purchase->items()->create([
            'orden' => 1,
            'cantidad' => 5,
            'descripcion' => 'CAMARA',
            'referencia' => 'CAM-1',
            'utilizacion' => 'OFICINA',
            'ubicacion' => 'CALI',
        ]);

        return $purchase->fresh('items');
    }
}
