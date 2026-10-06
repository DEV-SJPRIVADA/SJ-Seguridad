<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use App\Models\ClienteInternoTipoSolicitud;
use App\Models\User;
use App\Services\GestionHumana\ClienteInternoBusinessDaysService;
use App\Support\PermissionCatalog;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteInternoSolicitudesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_solicitudes_page_includes_datatable_filters_and_export(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create(['name' => 'Certificado laboral']);
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'anio' => 2026,
            'mes' => 3,
            'fecha_solicitud' => '2026-03-10',
        ]);

        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.solicitudes', [
                'anio' => '2026',
                'mes' => '3',
            ]))
            ->assertOk()
            ->assertViewHas('datatableUrl')
            ->assertViewHas('exportUrl')
            ->assertSee('js-cliente-interno-solicitudes-datatable', false)
            ->assertSee('serverSide: true', false)
            ->assertSee('>Mes</th>', false)
            ->assertSee('filter_anio', false)
            ->assertSee('filter_estado_id', false)
            ->assertSee(route('gestion-humana.cliente-interno.solicitudes.export'), false)
            ->assertDontSee('Plantilla e importar', false);
    }

    public function test_viewer_cannot_mutate_solicitudes(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $solicitud = ClienteInternoSolicitud::factory()->create(['tipo_solicitud_id' => $tipo->id]);
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), $this->validPayload($tipo->id))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('gestion-humana.cliente-interno.solicitudes.update', $solicitud), $this->validPayload($tipo->id))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('gestion-humana.cliente-interno.solicitudes.destroy', $solicitud))
            ->assertForbidden();
    }

    public function test_store_creates_solicitud_and_derives_anio_mes(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $estado = ClienteInternoEstado::query()->where('code', 'PENDIENTE')->firstOrFail();
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), [
                'fecha_solicitud' => '2026-04-15',
                'nombre_apellidos' => 'Ana Pérez',
                'cedula' => '1234567890',
                'correo_electronico' => 'ana@example.com',
                'tipo_solicitud_id' => $tipo->id,
                'estado_id' => $estado->id,
                'novedad' => 'Prueba',
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.solicitudes'));

        $this->assertDatabaseHas('cliente_interno_solicitudes', [
            'cedula' => '1234567890',
            'nombre_apellidos' => 'Ana Pérez',
            'anio' => 2026,
            'mes' => 4,
            'tipo_solicitud_id' => $tipo->id,
            'estado_id' => $estado->id,
            'dias_respuesta' => null,
            'dias_respuesta_manual' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'cliente_interno_solicitud',
            'action' => 'create',
        ]);
    }

    public function test_store_requires_mandatory_fields(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.cliente-interno.solicitudes'))
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), [])
            ->assertRedirect(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertSessionHasErrors(['fecha_solicitud', 'nombre_apellidos', 'cedula', 'tipo_solicitud_id']);
    }

    public function test_store_calculates_business_days_auto(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $editor = $this->editorUser();

        // Lun 2026-03-02 → Vie 2026-03-06 = 4 hábiles (mar–vie)
        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), [
                'fecha_solicitud' => '2026-03-02',
                'nombre_apellidos' => 'Hábiles Auto',
                'cedula' => '111',
                'tipo_solicitud_id' => $tipo->id,
                'fecha_respuesta' => '2026-03-06',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cliente_interno_solicitudes', [
            'cedula' => '111',
            'dias_respuesta' => 4,
            'dias_respuesta_manual' => 0,
        ]);
    }

    public function test_store_manual_override_when_dias_differ(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), [
                'fecha_solicitud' => '2026-03-02',
                'nombre_apellidos' => 'Override Alta',
                'cedula' => '222',
                'tipo_solicitud_id' => $tipo->id,
                'fecha_respuesta' => '2026-03-06',
                'dias_respuesta' => 9,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cliente_interno_solicitudes', [
            'cedula' => '222',
            'dias_respuesta' => 9,
            'dias_respuesta_manual' => 1,
        ]);
    }

    public function test_update_keeps_manual_override_when_dates_change_without_touching_dias(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $solicitud = ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'fecha_solicitud' => '2026-03-02',
            'fecha_respuesta' => '2026-03-06',
            'anio' => 2026,
            'mes' => 3,
            'dias_respuesta' => 9,
            'dias_respuesta_manual' => true,
            'cedula' => '333',
            'nombre_apellidos' => 'Override Conservado',
        ]);
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cliente-interno.solicitudes.update', $solicitud), [
                'fecha_solicitud' => '2026-03-02',
                'nombre_apellidos' => 'Override Conservado',
                'cedula' => '333',
                'tipo_solicitud_id' => $tipo->id,
                'fecha_respuesta' => '2026-03-13',
                'dias_respuesta' => 9,
                'dias_respuesta_touched' => 0,
            ])
            ->assertRedirect();

        $solicitud->refresh();
        $this->assertSame(9, $solicitud->dias_respuesta);
        $this->assertTrue($solicitud->dias_respuesta_manual);
    }

    public function test_update_recalculates_when_recalcular_dias(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $solicitud = ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'fecha_solicitud' => '2026-03-02',
            'fecha_respuesta' => '2026-03-06',
            'anio' => 2026,
            'mes' => 3,
            'dias_respuesta' => 99,
            'dias_respuesta_manual' => true,
            'cedula' => '444',
            'nombre_apellidos' => 'Recalcular',
        ]);
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cliente-interno.solicitudes.update', $solicitud), [
                'fecha_solicitud' => '2026-03-02',
                'nombre_apellidos' => 'Recalcular',
                'cedula' => '444',
                'tipo_solicitud_id' => $tipo->id,
                'fecha_respuesta' => '2026-03-06',
                'dias_respuesta' => 99,
                'recalcular_dias' => 1,
            ])
            ->assertRedirect();

        $solicitud->refresh();
        $this->assertSame(4, $solicitud->dias_respuesta);
        $this->assertFalse($solicitud->dias_respuesta_manual);
    }

    public function test_update_and_destroy_solicitud(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $solicitud = ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'cedula' => '555',
            'nombre_apellidos' => 'Antes',
            'fecha_solicitud' => '2026-01-10',
            'anio' => 2026,
            'mes' => 1,
        ]);
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cliente-interno.solicitudes.update', $solicitud), [
                'fecha_solicitud' => '2026-05-20',
                'nombre_apellidos' => 'Después',
                'cedula' => '555',
                'tipo_solicitud_id' => $tipo->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cliente_interno_solicitudes', [
            'id' => $solicitud->id,
            'nombre_apellidos' => 'Después',
            'anio' => 2026,
            'mes' => 5,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cliente-interno.solicitudes.destroy', $solicitud))
            ->assertRedirect(route('gestion-humana.cliente-interno.solicitudes'));

        $this->assertDatabaseMissing('cliente_interno_solicitudes', ['id' => $solicitud->id]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'cliente_interno_solicitud',
            'action' => 'delete',
        ]);
    }

    public function test_datatable_responds_and_filters(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create(['name' => 'Constancia']);
        $estado = ClienteInternoEstado::query()->where('code', 'PENDIENTE')->firstOrFail();

        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'estado_id' => $estado->id,
            'cedula' => '900100',
            'nombre_apellidos' => 'Incluida Filtro',
            'fecha_solicitud' => '2026-02-10',
            'anio' => 2026,
            'mes' => 2,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'cedula' => '900200',
            'nombre_apellidos' => 'Excluida Filtro',
            'fecha_solicitud' => '2025-08-01',
            'anio' => 2025,
            'mes' => 8,
        ]);

        $viewer = $this->viewerUser();

        $response = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.solicitudes.datatable', [
                'anio' => 2026,
                'mes' => 2,
                'estado_id' => $estado->id,
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $rowText = $this->datatableRowText($response->json('data'));
        $this->assertStringContainsString('900100', $rowText);
        $this->assertStringContainsString('Incluida Filtro', $rowText);
        $this->assertStringContainsString('FEBRERO', $rowText);
        $this->assertStringNotContainsString('900200', $rowText);
    }

    public function test_export_respects_filters_and_audits(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();

        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'nombre_apellidos' => 'Export Incluida',
            'cedula' => '7001',
            'anio' => 2026,
            'mes' => 2,
            'fecha_solicitud' => '2026-02-01',
        ]);
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $tipo->id,
            'nombre_apellidos' => 'Export Excluida',
            'cedula' => '7002',
            'anio' => 2025,
            'mes' => 8,
            'fecha_solicitud' => '2025-08-01',
        ]);

        $viewer = $this->viewerUser();

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.solicitudes.export', [
                'anio' => 2026,
            ]))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'ci-export-');
        file_put_contents($temp, $response->streamedContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($temp));
        $sharedStrings = (string) $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if (is_file($temp)) {
            unlink($temp);
        }

        $this->assertStringContainsString('Export Incluida', $sharedStrings);
        $this->assertStringNotContainsString('Export Excluida', $sharedStrings);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'export',
            'action' => 'solicitudes_excel',
        ]);
    }

    public function test_datatable_and_export_require_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.cliente-interno.solicitudes.datatable'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.solicitudes.export'))
            ->assertForbidden();
    }

    public function test_edit_permission_implies_view_for_datatable_and_export(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.edit',
        ]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.cliente-interno.solicitudes.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.solicitudes.export'))
            ->assertOk();
    }

    public function test_business_days_service_fixed_cases(): void
    {
        $service = app(ClienteInternoBusinessDaysService::class);

        // Lun → Vie = 4 (mar, mié, jue, vie)
        $this->assertSame(
            4,
            $service->count(Carbon::parse('2026-03-02'), Carbon::parse('2026-03-06'))
        );

        // Mismo día = 0
        $this->assertSame(
            0,
            $service->count(Carbon::parse('2026-03-02'), Carbon::parse('2026-03-02'))
        );

        // Lun → mar = 1
        $this->assertSame(
            1,
            $service->count(Carbon::parse('2026-03-02'), Carbon::parse('2026-03-03'))
        );

        // Sin respuesta
        $this->assertNull($service->count(Carbon::parse('2026-03-02'), null));

        // Cruza fin de semana: vie → lun = 1 (solo el lunes)
        $this->assertSame(
            1,
            $service->count(Carbon::parse('2026-03-06'), Carbon::parse('2026-03-09'))
        );
    }

    public function test_allows_duplicate_cedula(): void
    {
        $tipo = ClienteInternoTipoSolicitud::factory()->create();
        $editor = $this->editorUser();

        $payload = [
            'fecha_solicitud' => '2026-01-05',
            'nombre_apellidos' => 'Duplicado Uno',
            'cedula' => 'DUP-01',
            'tipo_solicitud_id' => $tipo->id,
        ];

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), $payload)
            ->assertRedirect();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.solicitudes.store'), array_merge($payload, [
                'nombre_apellidos' => 'Duplicado Dos',
                'fecha_solicitud' => '2026-02-05',
            ]))
            ->assertRedirect();

        $this->assertSame(2, ClienteInternoSolicitud::query()->where('cedula', 'DUP-01')->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(int $tipoId): array
    {
        return [
            'fecha_solicitud' => '2026-01-15',
            'nombre_apellidos' => 'Persona Prueba',
            'cedula' => '999888777',
            'tipo_solicitud_id' => $tipoId,
        ];
    }

    /**
     * @param  list<list<string>>|null  $rows
     */
    private function datatableRowText(?array $rows): string
    {
        return collect($rows ?? [])
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');
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

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.view',
            'cliente_interno.solicitudes.edit',
        ]);

        return $user;
    }
}
