<?php

namespace Tests\Feature\Calidad;

use App\Models\AuditLog;
use App\Models\CommercialClient;
use App\Models\RequisitionCity;
use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCasoAdjunto;
use App\Models\ServicioClienteCategoria;
use App\Models\ServicioClienteFuente;
use App\Models\ServicioClienteResponsable;
use App\Models\ServicioClienteTipologia;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServicioClienteFoGi22Test extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private RequisitionCity $city;

    private ServicioClienteTipologia $tipologia;

    private ServicioClienteFuente $fuente;

    private ServicioClienteCategoria $categoria;

    private ServicioClienteResponsable $responsable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');

        $this->editor = User::factory()->create(['must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.servicio_cliente',
            'servicio_cliente.fo_gi_22.edit',
        ]);

        $this->city = RequisitionCity::query()->create(['name' => 'CALI', 'is_active' => true, 'sort_order' => 1]);
        $this->tipologia = ServicioClienteTipologia::query()->where('code', 'QUEJA')->firstOrFail();
        $this->fuente = ServicioClienteFuente::query()->where('code', 'WHATSAPP')->firstOrFail();
        $this->categoria = ServicioClienteCategoria::query()->create([
            'code' => 'SERVICIO',
            'name' => 'SERVICIO',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->responsable = ServicioClienteResponsable::query()->create([
            'code' => 'COORDINADOR',
            'name' => 'COORDINADOR DE CALIDAD',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_create_page_uses_the_requisition_form_shell(): void
    {
        $this->actingAs($this->editor)
            ->get(route('calidad.servicio-cliente.fo-gi-22.create'))
            ->assertOk()
            ->assertSee('Nuevo caso FO-GI-22')
            ->assertSee('Antes de crear')
            ->assertSee('Fecha de recepción')
            ->assertSee('Estado y responsable')
            ->assertSee('Elegir archivos')
            ->assertDontSee('Fecha de ejecución')
            ->assertDontSee('Días hábiles');
    }

    public function test_interno_case_copies_dates_and_assigns_consecutive(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
                'tipo_cliente' => 'interno',
                'fecha_recepcion' => '2026-11-06',
                'fecha_verificacion' => '2026-11-20',
                'descripcion' => 'cliente molesto',
                'responsable_seguimiento' => 'ana pérez',
            ]))
            ->assertRedirect(route('calidad.servicio-cliente.fo-gi-22.index'));

        $caso = ServicioClienteCaso::query()->firstOrFail();

        $this->assertSame(1, $caso->consecutivo);
        $this->assertSame('SJSEGURIDAD', $caso->cliente_nombre);
        $this->assertNull($caso->commercial_client_id);
        $this->assertSame('2026-11-06', $caso->fecha_recepcion->toDateString());
        $this->assertSame('2026-11-06', $caso->fecha_ejecucion->toDateString());
        $this->assertSame('2026-11-20', $caso->fecha_plazo_respuesta->toDateString());
        $this->assertSame(11, $caso->dias_habiles);
        $this->assertSame('CLIENTE MOLESTO', $caso->descripcion);
        $this->assertSame('ANA PÉREZ', $caso->responsable_seguimiento);

        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
                'tipo_cliente' => 'interno',
                'fecha_recepcion' => '2026-11-06',
            ]))
            ->assertRedirect();

        $this->assertSame(2, ServicioClienteCaso::query()->max('consecutivo'));
    }

    public function test_externo_requires_commercial_client_and_snapshots_the_name(): void
    {
        $this->actingAs($this->editor)
            ->from(route('calidad.servicio-cliente.fo-gi-22.create'))
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
                'tipo_cliente' => 'externo',
            ]))
            ->assertRedirect(route('calidad.servicio-cliente.fo-gi-22.create'))
            ->assertSessionHasErrors('commercial_client_id');

        $client = CommercialClient::query()->create([
            'nit' => '900123',
            'name' => 'acme ltda',
            'created_by' => $this->editor->id,
            'updated_by' => $this->editor->id,
        ]);

        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
                'tipo_cliente' => 'externo',
                'commercial_client_id' => $client->id,
            ]))
            ->assertRedirect();

        $this->assertSame('ACME LTDA', ServicioClienteCaso::query()->firstOrFail()->cliente_nombre);
    }

    public function test_cannot_close_without_evidence_and_can_close_with_pdf(): void
    {
        $this->actingAs($this->editor)
            ->from(route('calidad.servicio-cliente.fo-gi-22.create'))
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
                'eficacia' => 'cerrado',
            ]))
            ->assertSessionHasErrors('attachments');

        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
                'eficacia' => 'cerrado',
                'attachments' => [UploadedFile::fake()->create('evidencia.pdf', 20, 'application/pdf')],
            ]))
            ->assertRedirect();

        $caso = ServicioClienteCaso::query()->firstOrFail();
        $this->assertSame('cerrado', $caso->eficacia);
        $this->assertSame(1, $caso->adjuntos()->count());

        $adjunto = $caso->adjuntos()->firstOrFail();

        $this->actingAs($this->editor)
            ->delete(route('calidad.servicio-cliente.fo-gi-22.adjuntos.destroy', [$caso, $adjunto]))
            ->assertSessionHasErrors('attachments');

        $this->assertSame(1, ServicioClienteCasoAdjunto::query()->count());
    }

    public function test_historial_lists_logged_changes_for_viewers(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload())
            ->assertRedirect(route('calidad.servicio-cliente.fo-gi-22.index'));

        $this->actingAs($this->editor)
            ->get(route('calidad.servicio-cliente.fo-gi-22.index'))
            ->assertOk()
            ->assertSee('Historial de cambios', false);

        $this->actingAs($this->editor)
            ->getJson(route('calidad.servicio-cliente.fo-gi-22.historial'))
            ->assertOk()
            ->assertJsonFragment(['action' => 'create', 'action_label' => 'Alta']);
    }

    public function test_observation_is_stored_in_uppercase_and_delete_is_audited(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload())
            ->assertRedirect();

        $caso = ServicioClienteCaso::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.servicio-cliente.fo-gi-22.observaciones.store', $caso), [
                'texto' => 'se llamó al cliente',
            ])
            ->assertRedirect();

        $observacion = $caso->observaciones()->firstOrFail();
        $this->assertSame('SE LLAMÓ AL CLIENTE', $observacion->texto);

        $this->actingAs($this->editor)
            ->patch(route('calidad.servicio-cliente.fo-gi-22.observaciones.update', [$caso, $observacion]), [
                'texto' => 'se reprogramó la visita',
                'observacion_id' => $observacion->id,
            ])
            ->assertRedirect(route('calidad.servicio-cliente.fo-gi-22.show', $caso).'#observaciones');

        $observacion->refresh();
        $this->assertSame('SE REPROGRAMÓ LA VISITA', $observacion->texto);
        $this->assertTrue(AuditLog::query()->where('module', 'servicio_cliente')->where('event_type', 'fo_gi_22_observacion')->where('action', 'update')->exists());

        $this->actingAs($this->editor)
            ->delete(route('calidad.servicio-cliente.fo-gi-22.observaciones.destroy', [$caso, $observacion]))
            ->assertRedirect(route('calidad.servicio-cliente.fo-gi-22.show', $caso).'#observaciones');

        $this->assertSame(0, $caso->observaciones()->count());
        $this->assertTrue(AuditLog::query()->where('module', 'servicio_cliente')->where('event_type', 'fo_gi_22_observacion')->where('action', 'delete')->exists());

        $this->actingAs($this->editor)
            ->delete(route('calidad.servicio-cliente.fo-gi-22.destroy', $caso))
            ->assertRedirect(route('calidad.servicio-cliente.fo-gi-22.index'));

        $this->assertSame(0, ServicioClienteCaso::query()->count());
        $this->assertTrue(AuditLog::query()->where('module', 'servicio_cliente')->where('action', 'delete')->exists());
    }

    public function test_datatable_and_export_respect_eficacia_filter(): void
    {
        $this->actingAs($this->editor)->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
            'eficacia' => 'abierto',
            'descripcion' => 'caso abierto',
        ]));
        $this->actingAs($this->editor)->post(route('calidad.servicio-cliente.fo-gi-22.store'), $this->payload([
            'eficacia' => 'en_proceso',
            'descripcion' => 'caso en curso',
        ]));

        $this->actingAs($this->editor)
            ->get(route('calidad.servicio-cliente.fo-gi-22.index'))
            ->assertOk()
            ->assertSee('module-tab module-tab--active">Abierto', false);

        $this->actingAs($this->editor)
            ->getJson(route('calidad.servicio-cliente.fo-gi-22.datatable', ['draw' => 1]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($this->editor)
            ->getJson(route('calidad.servicio-cliente.fo-gi-22.datatable', ['eficacia' => 'abierto', 'draw' => 1]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($this->editor)
            ->getJson(route('calidad.servicio-cliente.fo-gi-22.datatable', ['eficacia' => 'todos', 'draw' => 1]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        $this->actingAs($this->editor)
            ->get(route('calidad.servicio-cliente.fo-gi-22.export', ['eficacia' => 'abierto']))
            ->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'fecha_recepcion' => '2026-11-06',
            'tipo_cliente' => 'interno',
            'tipologia_id' => $this->tipologia->id,
            'fuente_id' => $this->fuente->id,
            'city_id' => $this->city->id,
            'descripcion' => 'descripcion del caso',
            'categoria_id' => $this->categoria->id,
            'correccion' => 'correccion inmediata',
            'accion_realizar' => 'accion a realizar',
            'responsable_id' => $this->responsable->id,
            'eficacia' => 'abierto',
        ], $overrides);
    }
}
