<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use App\Models\ClienteInternoTipoSolicitud;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteInternoCatalogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_catalogos_forbidden_without_parameters_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.catalogos'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cliente-interno.catalogos.store', ['type' => 'estados']), [
                'code' => 'X',
                'name' => 'X',
                'is_active' => '1',
                'sort_order' => 1,
            ])
            ->assertForbidden();
    }

    public function test_parameters_user_sees_seed_estados_and_empty_tipos_solicitud(): void
    {
        $editor = $this->parametersUser();

        $this->assertSame(4, ClienteInternoEstado::query()->count());
        $this->assertSame(0, ClienteInternoTipoSolicitud::query()->count());

        $this->actingAs($editor)
            ->get(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'estados']))
            ->assertOk()
            ->assertSee('PENDIENTE', false)
            ->assertSee('Pendiente', false)
            ->assertSee('EN_PROCESO', false)
            ->assertSee('RESPONDIDA', false)
            ->assertSee('CERRADA', false);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'tipos-solicitud']))
            ->assertOk()
            ->assertSee('Aún no hay tipos de solicitud', false);
    }

    public function test_store_rejects_unknown_catalog_type(): void
    {
        $editor = $this->parametersUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.catalogos.store', ['type' => 'novedades']), [
                'code' => 'X1',
                'name' => 'Inventado',
                'is_active' => '1',
                'sort_order' => 1,
            ])
            ->assertNotFound();
    }

    public function test_store_creates_estado_and_tipo_solicitud(): void
    {
        $editor = $this->parametersUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.catalogos.store', ['type' => 'estados']), [
                'code' => 'REABIERTA',
                'name' => 'Reabierta',
                'is_active' => '1',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'estados']));

        $this->assertDatabaseHas('cliente_interno_estados', [
            'code' => 'REABIERTA',
            'name' => 'Reabierta',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'cliente_interno_catalog',
            'action' => 'create',
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.catalogos.store', ['type' => 'tipos-solicitud']), [
                'code' => 'CERTIFICADO',
                'name' => 'Certificado laboral',
                'is_active' => '1',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'tipos-solicitud']));

        $this->assertDatabaseHas('cliente_interno_tipos_solicitud', [
            'code' => 'CERTIFICADO',
            'name' => 'Certificado laboral',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_update_and_destroy_catalog_item(): void
    {
        $editor = $this->parametersUser();

        $tipo = ClienteInternoTipoSolicitud::factory()->create([
            'code' => 'TEMP_TIPO',
            'name' => 'Temporal',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cliente-interno.catalogos.update', [
                'type' => 'tipos-solicitud',
                'item' => $tipo->id,
            ]), [
                'code' => 'TEMP_TIPO',
                'name' => 'Temporal editado',
                'is_active' => '0',
                'sort_order' => 11,
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'tipos-solicitud']));

        $this->assertDatabaseHas('cliente_interno_tipos_solicitud', [
            'id' => $tipo->id,
            'name' => 'Temporal editado',
            'is_active' => false,
            'sort_order' => 11,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cliente-interno.catalogos.destroy', [
                'type' => 'tipos-solicitud',
                'item' => $tipo->id,
            ]))
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'tipos-solicitud']));

        $this->assertDatabaseMissing('cliente_interno_tipos_solicitud', [
            'id' => $tipo->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'cliente_interno_catalog',
            'action' => 'delete',
        ]);
    }

    public function test_destroy_blocked_when_estado_has_solicitudes_but_deactivate_ok(): void
    {
        $editor = $this->parametersUser();

        $estado = ClienteInternoEstado::query()->where('code', 'PENDIENTE')->firstOrFail();
        $tipo = ClienteInternoTipoSolicitud::factory()->create();

        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'estado_id' => $estado->id,
        ]);

        $this->actingAs($editor)
            ->from(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'estados']))
            ->delete(route('gestion-humana.cliente-interno.catalogos.destroy', [
                'type' => 'estados',
                'item' => $estado->id,
            ]))
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'estados']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cliente_interno_estados', [
            'id' => $estado->id,
            'code' => 'PENDIENTE',
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cliente-interno.catalogos.update', [
                'type' => 'estados',
                'item' => $estado->id,
            ]), [
                'code' => 'PENDIENTE',
                'name' => 'Pendiente',
                'is_active' => '0',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'estados']));

        $this->assertDatabaseHas('cliente_interno_estados', [
            'id' => $estado->id,
            'is_active' => false,
        ]);
    }

    public function test_destroy_blocked_when_tipo_solicitud_has_references(): void
    {
        $editor = $this->parametersUser();
        $tipo = ClienteInternoTipoSolicitud::factory()->create([
            'code' => 'CON_REF',
            'name' => 'Con referencia',
        ]);

        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cliente-interno.catalogos.destroy', [
                'type' => 'tipos-solicitud',
                'item' => $tipo->id,
            ]))
            ->assertRedirect(route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'tipos-solicitud']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cliente_interno_tipos_solicitud', [
            'id' => $tipo->id,
            'code' => 'CON_REF',
        ]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.view',
        ]);

        return $user;
    }

    private function parametersUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.parameters.edit',
        ]);

        return $user;
    }
}
