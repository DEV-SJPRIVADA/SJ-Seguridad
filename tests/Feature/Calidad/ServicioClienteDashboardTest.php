<?php

namespace Tests\Feature\Calidad;

use App\Models\RequisitionCity;
use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCategoria;
use App\Models\ServicioClienteFuente;
use App\Models\ServicioClienteResponsable;
use App\Models\ServicioClienteTipologia;
use App\Models\User;
use App\Services\Calidad\ServicioClienteCasoService;
use App\Services\Calidad\ServicioClienteDashboardService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicioClienteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_overdue_and_recurring_clients(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.calidad.servicio_cliente',
            'servicio_cliente.fo_gi_22.view',
        ]);

        $city = RequisitionCity::query()->create(['name' => 'CALI', 'is_active' => true, 'sort_order' => 1]);
        $categoria = ServicioClienteCategoria::query()->create([
            'code' => 'SERVICIO',
            'name' => 'SERVICIO',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $responsable = ServicioClienteResponsable::query()->create([
            'code' => 'COORDINADOR',
            'name' => 'COORDINADOR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service = app(ServicioClienteCasoService::class);
        $base = [
            'tipo_cliente' => 'interno',
            'tipologia_id' => ServicioClienteTipologia::query()->where('code', 'QUEJA')->value('id'),
            'fuente_id' => ServicioClienteFuente::query()->where('code', 'WHATSAPP')->value('id'),
            'city_id' => $city->id,
            'descripcion' => 'CASO',
            'categoria_id' => $categoria->id,
            'correccion' => 'CORRECCION',
            'accion_realizar' => 'ACCION',
            'responsable_id' => $responsable->id,
            'eficacia' => 'abierto',
            'fecha_recepcion' => '2026-01-05',
        ];

        $service->create($base, [], $user->id);
        $service->create($base, [], $user->id);
        $cerrado = $service->create(array_merge($base, [
            'fecha_recepcion' => '2026-11-02',
        ]), [], $user->id);
        $cerrado->update(['eficacia' => ServicioClienteCaso::EFICACIA_CERRADO]);

        $metrics = app(ServicioClienteDashboardService::class)->metrics([
            'recepcion_desde' => '2026-01-01',
            'recepcion_hasta' => '2026-12-31',
            'recurrencia' => 'mes',
            'tipo_cliente' => '',
            'tipologia_id' => '',
            'fuente_id' => '',
            'responsable_id' => '',
            'eficacia' => '',
            'verificacion_desde' => '',
            'verificacion_hasta' => '',
            'plazo_desde' => '',
            'plazo_hasta' => '',
        ]);

        $this->assertSame(3, $metrics['kpis']['total']);
        $this->assertSame(2, $metrics['kpis']['vencidos']);
        $this->assertSame(1, $metrics['kpis']['cerrados']);
        $this->assertSame('SJSEGURIDAD', $metrics['recurrencia']['clientes'][0]['nombre']);
        $this->assertSame(3, $metrics['recurrencia']['clientes'][0]['total']);

        $this->actingAs($user)
            ->get(route('calidad.servicio-cliente.dashboard', [
                'recepcion_desde' => '2026-01-01',
                'recepcion_hasta' => '2026-12-31',
                'recurrencia' => 'mes',
            ]))
            ->assertOk()
            ->assertSee('Vencidos')
            ->assertSee('Cantidad por tipo de solicitud')
            ->assertSee('recepcion_desde=2026-01-01', false)
            ->assertSee('eficacia=abierto', false)
            ->assertSee('eficacia=en_proceso', false)
            ->assertSee('eficacia=cerrado', false)
            ->assertSee('eficacia=todos', false)
            ->assertSee('vencidos=1', false);

        $this->actingAs($user)
            ->getJson(route('calidad.servicio-cliente.fo-gi-22.datatable', [
                'draw' => 1,
                'eficacia' => 'todos',
                'vencidos' => '1',
                'recepcion_desde' => '2026-01-01',
                'recepcion_hasta' => '2026-12-31',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        $this->assertSame(3, ServicioClienteCaso::query()->count());
    }
}
