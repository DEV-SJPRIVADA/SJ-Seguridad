<?php

namespace Tests\Feature\Calidad;

use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtGi11CatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $this->manager->givePermissionTo([
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.parameters.edit',
        ]);

        $this->editor = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
            'mt_gi_11.matriz.edit',
        ]);
    }

    public function test_create_catalog_item_generates_technical_code(): void
    {
        $this->actingAs($this->manager)
            ->post(route('calidad.mt-gi-11.catalogos.store', 'cargos_responsable'), [
                'name' => 'Coordinador de calidad',
                'is_active' => 1,
            ])
            ->assertRedirect(route('calidad.mt-gi-11.catalogos', ['catalog' => 'cargos_responsable']));

        $item = MtGi11CargoResponsable::query()->where('name', 'COORDINADOR DE CALIDAD')->firstOrFail();
        $this->assertSame('COORDINADOR_DE_CALIDAD', $item->code);
        $this->assertTrue($item->is_active);
    }

    public function test_create_estado_stores_color(): void
    {
        $this->actingAs($this->manager)
            ->post(route('calidad.mt-gi-11.catalogos.store', 'estados'), [
                'name' => 'En verificación',
                'color' => 'info',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $estado = MtGi11Estado::query()->where('code', 'EN_VERIFICACION')->firstOrFail();
        $this->assertSame('info', $estado->color);
        $this->assertSame('info', $estado->pillClass());
    }

    public function test_invalid_color_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->post(route('calidad.mt-gi-11.catalogos.store', 'estados'), [
                'name' => 'Prueba',
                'color' => 'purple',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_update_catalog_item(): void
    {
        $proceso = MtGi11Proceso::query()->firstOrFail();

        $this->actingAs($this->manager)
            ->patch(route('calidad.mt-gi-11.catalogos.update', ['procesos', $proceso->id]), [
                'name' => 'Proceso renombrado',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('PROCESO RENOMBRADO', $proceso->refresh()->name);
    }

    public function test_cannot_delete_catalog_value_in_use(): void
    {
        $proceso = MtGi11Proceso::query()->firstOrFail();

        MtGi11Solicitud::query()->create([
            'fecha_solicitud' => now()->toDateString(),
            'created_by' => $this->editor->id,
            'proceso_id' => $proceso->id,
            'consecutivo' => 'MT-GI-11-777',
            'tipo_documento_id' => MtGi11TipoDocumento::query()->firstOrFail()->id,
            'nombre_documento' => 'DOC',
            'tipo_solicitud_id' => MtGi11TipoSolicitud::query()->firstOrFail()->id,
            'justificacion' => 'JUST',
            'responsable_proceso' => 'RESP',
            'cargo_responsable_id' => MtGi11CargoResponsable::query()->create([
                'code' => 'JEFE',
                'name' => 'JEFE',
            ])->id,
            'estado_id' => MtGi11Estado::query()->where('code', 'PENDIENTE')->value('id'),
        ]);

        $this->actingAs($this->manager)
            ->delete(route('calidad.mt-gi-11.catalogos.destroy', ['procesos', $proceso->id]))
            ->assertSessionHasErrors('catalog');

        $this->assertDatabaseHas('calidad_mt_gi_11_procesos', ['id' => $proceso->id]);
    }

    public function test_can_delete_unused_catalog_value(): void
    {
        $cargo = MtGi11CargoResponsable::query()->create([
            'code' => 'SIN_USO',
            'name' => 'SIN USO',
        ]);

        $this->actingAs($this->manager)
            ->delete(route('calidad.mt-gi-11.catalogos.destroy', ['cargos_responsable', $cargo->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('calidad_mt_gi_11_cargos_responsable', ['id' => $cargo->id]);
    }

    public function test_editor_cannot_manage_catalogs(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.mt-gi-11.catalogos.store', 'procesos'), ['name' => 'X'])
            ->assertForbidden();
    }

    public function test_unknown_catalog_type_returns_404(): void
    {
        $this->actingAs($this->manager)
            ->post(route('calidad.mt-gi-11.catalogos.store', 'no_existe'), ['name' => 'X'])
            ->assertNotFound();
    }
}
