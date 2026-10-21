<?php

namespace Tests\Feature\PurchaseRequests;

use App\Enums\PedidoEtapa;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etapa común: mismo nombre para compras y suministros sin cambiar los valores en BD.
 */
class PedidoEtapaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_purchase_columns_map_to_the_common_stage(): void
    {
        $this->assertSame(PedidoEtapa::PorAutorizar, PedidoEtapa::fromPurchase('pendiente', null));
        $this->assertSame(PedidoEtapa::Devuelta, PedidoEtapa::fromPurchase('rechazado', null));
        $this->assertSame(PedidoEtapa::PorComprar, PedidoEtapa::fromPurchase('aprobado', 'pendiente'));
        $this->assertSame(PedidoEtapa::PorComprar, PedidoEtapa::fromPurchase('aprobado', null));
        $this->assertSame(PedidoEtapa::ConOrden, PedidoEtapa::fromPurchase('aprobado', 'en_curso'));
        $this->assertSame(PedidoEtapa::Cerrado, PedidoEtapa::fromPurchase('aprobado', 'completado'));
        $this->assertSame(PedidoEtapa::CerradoSinCompra, PedidoEtapa::fromPurchase('aprobado', 'rechazado'));
    }

    public function test_supply_status_maps_to_the_same_stages(): void
    {
        $this->assertSame(PedidoEtapa::PorAutorizar, PedidoEtapa::fromSupply('pendiente_calidad'));
        $this->assertSame(PedidoEtapa::Devuelta, PedidoEtapa::fromSupply('rechazada_calidad'));
        $this->assertSame(PedidoEtapa::PorComprar, PedidoEtapa::fromSupply('aprobada_calidad'));
        $this->assertSame(PedidoEtapa::ConOrden, PedidoEtapa::fromSupply('en_compras'));
        $this->assertSame(PedidoEtapa::Cerrado, PedidoEtapa::fromSupply('completada'));

        $supply = new SupplyRequest(['status' => 'en_compras']);
        $this->assertSame('Con orden', $supply->statusLabel());
        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $supply->estadoComprasKey());
    }

    public function test_where_etapa_scopes_match_the_mapping(): void
    {
        $owner = User::factory()->create(['must_change_password' => false]);

        foreach (['pendiente_calidad', 'rechazada_calidad', 'aprobada_calidad', 'en_compras', 'completada'] as $status) {
            SupplyRequest::query()->create([
                'user_id' => $owner->id,
                'area_key' => 'operaciones',
                'status' => $status,
            ]);
        }

        $purchases = [
            ['pendiente', null],
            ['rechazado', null],
            ['aprobado', 'pendiente'],
            ['aprobado', 'en_curso'],
            ['aprobado', 'completado'],
        ];

        foreach ($purchases as $index => [$estado, $compras]) {
            PurchaseRequest::query()->create([
                'numero_solicitud' => $index + 1,
                'user_id' => $owner->id,
                'area_key' => 'operaciones',
                'fecha_solicitud' => now()->toDateString(),
                'cantidad' => 1,
                'solicitud_para' => 'Interno',
                'urgente' => false,
                'aprobador_id' => $owner->id,
                'estado' => $estado,
                'estado_compras' => $compras,
            ]);
        }

        foreach ([PedidoEtapa::PorAutorizar, PedidoEtapa::Devuelta, PedidoEtapa::PorComprar, PedidoEtapa::ConOrden, PedidoEtapa::Cerrado] as $etapa) {
            $this->assertSame(1, SupplyRequest::query()->whereEtapa($etapa)->count(), $etapa->value);
            $this->assertSame(1, PurchaseRequest::query()->whereEtapa($etapa)->count(), $etapa->value);
            $this->assertTrue(SupplyRequest::query()->whereEtapa($etapa)->firstOrFail()->etapa() === $etapa);
            $this->assertTrue(PurchaseRequest::query()->whereEtapa($etapa)->firstOrFail()->etapa() === $etapa);
        }

        $this->assertSame(2, SupplyRequest::query()->whereEtapa([PedidoEtapa::PorComprar, PedidoEtapa::ConOrden])->count());
        $this->assertSame(0, SupplyRequest::query()->whereEtapa([])->count());
        $this->assertSame(0, PurchaseRequest::query()->whereEtapa(PedidoEtapa::CerradoSinCompra)->count());
    }
}
