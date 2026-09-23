<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcreditacionesAcreditadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-23', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_viewer_gets_403_on_mutations_and_can_view_list_export(): void
    {
        $viewer = $this->viewerUser();
        $cargo = $this->activeCargo('VIGILANTE');
        $this->createFicha('1001', 'Persona View');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertOk()
            ->assertDontSee('Próximamente', false);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), $this->validPayload([
                'document_number' => '1001',
                'cargo_apo' => $cargo->cargo_apo,
            ]))
            ->assertForbidden();

        $row = AcreditacionAcreditado::factory()->create([
            'document_number' => '1001',
            'full_name' => 'Persona View',
            'cargo_apo' => $cargo->cargo_apo,
            'vigencia_acr' => '2027-01-01',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $this->actingAs($viewer)
            ->patch(route('gestion-humana.acreditaciones.acreditados.update', $row), $this->validPayload([
                'document_number' => '1001',
                'cargo_apo' => $cargo->cargo_apo,
                'cargo' => 'EDIT',
            ]))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('gestion-humana.acreditaciones.acreditados.destroy', $row))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados.export'))
            ->assertOk();
    }

    public function test_create_requires_ficha_and_sets_full_name_and_estado(): void
    {
        $editor = $this->editorUser();
        $cargo = $this->activeCargo('VIGILANTE');

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), $this->validPayload([
                'document_number' => '9999',
                'cargo_apo' => $cargo->cargo_apo,
                'vigencia_acr' => '2027-01-01',
                'full_name' => 'Nombre Del Cliente Ignorado',
            ]))
            ->assertSessionHasErrors('document_number');

        $this->createFicha('9999', 'Nombre Desde Ficha');

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), $this->validPayload([
                'document_number' => '9999',
                'cargo_apo' => $cargo->cargo_apo,
                'vigencia_acr' => '2027-01-01',
                'full_name' => 'Nombre Del Cliente Ignorado',
                'estado' => 'DESACREDITADO',
            ]))
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'));

        $row = AcreditacionAcreditado::query()->where('document_number', '9999')->first();
        $this->assertNotNull($row);
        $this->assertSame('Nombre Desde Ficha', $row->full_name);
        $this->assertSame(AcreditacionAcreditado::ESTADO_ACREDITADO, $row->estado);
    }

    public function test_both_dates_empty_returns_422_validation(): void
    {
        $editor = $this->editorUser();
        $cargo = $this->activeCargo('ESCOLTA');
        $this->createFicha('2002', 'Sin Fechas');

        $this->actingAs($editor)
            ->from(route('gestion-humana.acreditaciones.acreditados'))
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), $this->validPayload([
                'document_number' => '2002',
                'cargo_apo' => $cargo->cargo_apo,
                'vigencia_acr' => null,
                'fecha_solicitud' => null,
            ]))
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'))
            ->assertSessionHasErrors('vigencia_acr');
    }

    public function test_estado_priority_en_proceso_and_unicidad(): void
    {
        $editor = $this->editorUser();
        $cargo = $this->activeCargo('SUPERVISOR');
        $this->createFicha('3003', 'Prioridad Estado');

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), $this->validPayload([
                'document_number' => '3003',
                'cargo_apo' => $cargo->cargo_apo,
                'vigencia_acr' => '2020-01-01',
                'fecha_solicitud' => '2026-09-01',
            ]))
            ->assertRedirect();

        $row = AcreditacionAcreditado::query()->where('document_number', '3003')->firstOrFail();
        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $row->estado);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), $this->validPayload([
                'document_number' => '3003',
                'cargo_apo' => $cargo->cargo_apo,
                'vigencia_acr' => '2027-06-01',
            ]))
            ->assertSessionHasErrors('cargo_apo');
    }

    public function test_editor_can_update_and_delete(): void
    {
        $editor = $this->editorUser();
        $cargo = $this->activeCargo('VIGILANTE');
        $this->createFicha('4004', 'CRUD Persona');

        $row = AcreditacionAcreditado::factory()->create([
            'document_number' => '4004',
            'full_name' => 'CRUD Persona',
            'cargo' => 'GUARDA',
            'cargo_apo' => $cargo->cargo_apo,
            'vigencia_acr' => '2027-01-01',
            'fecha_solicitud' => null,
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.acreditaciones.acreditados.update', $row), $this->validPayload([
                'document_number' => '4004',
                'cargo' => 'GUARDA EDIT',
                'cargo_apo' => $cargo->cargo_apo,
                'vigencia_acr' => now()->addDays(5)->toDateString(),
            ]))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('GUARDA EDIT', $row->cargo);
        $this->assertSame(AcreditacionAcreditado::ESTADO_POR_VENCER, $row->estado);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.acreditaciones.acreditados.destroy', $row))
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'));

        $this->assertDatabaseMissing('acreditacion_acreditados', ['id' => $row->id]);
    }

    public function test_datatable_filters_by_estado_and_vigencia(): void
    {
        $viewer = $this->viewerUser();
        $cargo = $this->activeCargo('VIGILANTE');
        $this->createFicha('5005', 'Filtro Uno');
        $this->createFicha('5006', 'Filtro Dos');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '5005',
            'full_name' => 'Filtro Uno',
            'cargo' => 'GUARDA',
            'cargo_apo' => $cargo->cargo_apo,
            'vigencia_acr' => '2026-10-01',
            'estado' => AcreditacionAcreditado::ESTADO_POR_VENCER,
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '5006',
            'full_name' => 'Filtro Dos',
            'cargo' => 'OTRO',
            'cargo_apo' => $cargo->cargo_apo,
            'vigencia_acr' => '2027-12-01',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $response = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.acreditados.datatable', [
                'estado' => AcreditacionAcreditado::ESTADO_POR_VENCER,
                'vigencia_desde' => '2026-09-01',
                'vigencia_hasta' => '2026-10-15',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $json = $response->json();
        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringContainsString('5005', $json['data'][0][0]);
    }

    public function test_export_respects_filters(): void
    {
        $viewer = $this->viewerUser();
        $cargo = $this->activeCargo('ESCOLTA');
        $this->createFicha('6006', 'Export Uno');
        $this->createFicha('6007', 'Export Dos');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '6006',
            'full_name' => 'Export Uno',
            'cargo_apo' => $cargo->cargo_apo,
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'vigencia_acr' => '2027-01-01',
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '6007',
            'full_name' => 'Export Dos',
            'cargo_apo' => $cargo->cargo_apo,
            'estado' => AcreditacionAcreditado::ESTADO_DESACREDITADO,
            'vigencia_acr' => '2026-01-01',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados.export', [
                'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $response->headers->get('content-type'),
        );
    }

    public function test_delete_last_active_apo_blocked_when_acreditados_exist(): void
    {
        $editor = $this->editorUser();
        $cargo = AcreditacionCargo::factory()->create([
            'cargo_manager' => 'UNICO REF',
            'cargo_apo' => 'APO REFERENCIADO',
            'is_active' => true,
        ]);
        $this->createFicha('7007', 'Con Ref');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '7007',
            'full_name' => 'Con Ref',
            'cargo_apo' => 'APO REFERENCIADO',
            'vigencia_acr' => '2027-01-01',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.acreditaciones.catalogo.destroy', $cargo))
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('acreditacion_cargos', ['id' => $cargo->id]);
    }

    public function test_rename_apo_blocked_when_acreditados_exist(): void
    {
        $editor = $this->editorUser();
        $cargo = AcreditacionCargo::factory()->create([
            'cargo_manager' => 'MANAGER RENAME',
            'cargo_apo' => 'APO OLD',
            'is_active' => true,
        ]);
        $this->createFicha('8008', 'Rename Ref');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '8008',
            'full_name' => 'Rename Ref',
            'cargo_apo' => 'APO OLD',
            'vigencia_acr' => '2027-01-01',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.acreditaciones.catalogo.update', $cargo), [
                'cargo_manager' => 'MANAGER RENAME',
                'cargo_apo' => 'APO NEW',
                'cargo_informe' => 'INFORME',
                'cargo_acreditacion' => '1',
                'is_active' => 1,
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('cargo_apo');
    }

    public function test_lookup_returns_ficha_name(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('9009', 'Lookup Nombre');

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.acreditados.lookup', ['cedula' => '9009']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'document_number' => '9009',
                'full_name' => 'Lookup Nombre',
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '1000',
            'cargo' => 'GUARDA',
            'cargo_apo' => 'VIGILANTE',
            'vigencia_acr' => '2027-01-01',
            'fecha_solicitud' => null,
            'observaciones' => null,
        ], $overrides);
    }

    private function createFicha(string $documentNumber, string $fullName): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
        ]);
    }

    private function activeCargo(string $cargoApo): AcreditacionCargo
    {
        return AcreditacionCargo::factory()->create([
            'cargo_apo' => $cargoApo,
            'is_active' => true,
        ]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}
