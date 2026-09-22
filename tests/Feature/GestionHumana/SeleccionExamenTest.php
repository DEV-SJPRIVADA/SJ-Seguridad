<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CommercialClient;
use App\Models\PayrollCatalogItem;
use App\Models\SeleccionExamenOcupacional;
use App\Models\SeleccionIngreso;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeleccionExamenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_store_creates_examen_with_required_fields(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.examenes.store'), $payload)
            ->assertRedirect(route('gestion-humana.seleccion.examenes'));

        $this->assertDatabaseHas('seleccion_examenes_ocupacionales', [
            'document_number' => $payload['document_number'],
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'city_code' => $payload['city_code'],
            'position_code' => $payload['position_code'],
            'eps_code' => $payload['eps_code'],
            'afp_code' => $payload['afp_code'],
            'marital_status_code' => $payload['marital_status_code'],
            'solicitud_status_code' => $payload['solicitud_status_code'],
            'responsable_user_id' => $payload['responsable_user_id'],
        ]);

        $row = SeleccionExamenOcupacional::query()->where('document_number', $payload['document_number'])->first();
        $this->assertNotNull($row);
        $this->assertSame('Cali', $row->city_name);
        $this->assertSame('Vigilante', $row->position_name);
        $this->assertNotEmpty($row->eps_name);
        $this->assertNotEmpty($row->solicitud_status_name);
    }

    public function test_store_duplicate_requires_confirm_flag(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionExamenOcupacional::factory()->create([
            'document_number' => $payload['document_number'],
            'commercial_client_id' => $payload['commercial_client_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'eps_code' => $payload['eps_code'],
            'eps_name' => 'EPS Test',
            'afp_code' => $payload['afp_code'],
            'afp_name' => 'AFP Test',
            'marital_status_code' => $payload['marital_status_code'],
            'marital_status_name' => 'Soltero',
            'solicitud_status_code' => $payload['solicitud_status_code'],
            'solicitud_status_name' => 'EN PROCESO',
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.seleccion.examenes.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confirm_duplicate', 'document_number']);

        $this->assertSame(1, SeleccionExamenOcupacional::query()->where('document_number', $payload['document_number'])->count());

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.examenes.store'), [
                ...$payload,
                'confirm_duplicate' => 1,
                'full_name' => 'Segundo Examen',
            ])
            ->assertRedirect(route('gestion-humana.seleccion.examenes'));

        $this->assertSame(2, SeleccionExamenOcupacional::query()->where('document_number', $payload['document_number'])->count());
    }

    public function test_duplicate_cedula_in_ingreso_does_not_block_examen(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionIngreso::factory()->create([
            'document_number' => $payload['document_number'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'commercial_client_id' => $payload['commercial_client_id'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.examenes.store'), $payload)
            ->assertRedirect(route('gestion-humana.seleccion.examenes'));

        $this->assertDatabaseHas('seleccion_examenes_ocupacionales', [
            'document_number' => $payload['document_number'],
        ]);
    }

    public function test_update_and_destroy_examen(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        $examen = SeleccionExamenOcupacional::factory()->create([
            'document_number' => '111222333',
            'commercial_client_id' => $payload['commercial_client_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'eps_code' => $payload['eps_code'],
            'eps_name' => 'EPS Test',
            'afp_code' => $payload['afp_code'],
            'afp_name' => 'AFP Test',
            'marital_status_code' => $payload['marital_status_code'],
            'marital_status_name' => 'Soltero',
            'solicitud_status_code' => $payload['solicitud_status_code'],
            'solicitud_status_name' => 'EN PROCESO',
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.seleccion.examenes.update', $examen), [
                ...$payload,
                'document_number' => '111222333',
                'full_name' => 'Nombre Actualizado',
                'confirm_duplicate' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('seleccion_examenes_ocupacionales', [
            'id' => $examen->id,
            'full_name' => 'Nombre Actualizado',
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.seleccion.examenes.destroy', $examen))
            ->assertRedirect(route('gestion-humana.seleccion.examenes'));

        $this->assertDatabaseMissing('seleccion_examenes_ocupacionales', [
            'id' => $examen->id,
        ]);
    }

    public function test_datatable_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.seleccion.examenes.datatable'))
            ->assertForbidden();

        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.examenes.datatable', ['draw' => 1]))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_export_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.seleccion.examenes.export'))
            ->assertForbidden();

        $viewer = $this->viewerUser();
        $payload = $this->validPayload($this->editorUser());

        SeleccionExamenOcupacional::factory()->create([
            'commercial_client_id' => $payload['commercial_client_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'eps_code' => $payload['eps_code'],
            'eps_name' => 'EPS Test',
            'afp_code' => $payload['afp_code'],
            'afp_name' => 'AFP Test',
            'marital_status_code' => $payload['marital_status_code'],
            'marital_status_name' => 'Soltero',
            'solicitud_status_code' => $payload['solicitud_status_code'],
            'solicitud_status_name' => 'EN PROCESO',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.examenes.export'))
            ->assertOk();
    }

    public function test_lookup_cedula_requires_edit_and_returns_matches(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionExamenOcupacional::factory()->create([
            'document_number' => '99887766',
            'commercial_client_id' => $payload['commercial_client_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'eps_code' => $payload['eps_code'],
            'eps_name' => 'EPS Test',
            'afp_code' => $payload['afp_code'],
            'afp_name' => 'AFP Test',
            'marital_status_code' => $payload['marital_status_code'],
            'marital_status_name' => 'Soltero',
            'solicitud_status_code' => $payload['solicitud_status_code'],
            'solicitud_status_name' => 'EN PROCESO',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.examenes.lookup-cedula', ['cedula' => '99887766']))
            ->assertForbidden();

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.seleccion.examenes.lookup-cedula', ['cedula' => '99887766']))
            ->assertOk()
            ->assertJsonPath('has_duplicates', true)
            ->assertJsonCount(1, 'matches');
    }

    public function test_catalog_destroy_blocked_when_marital_status_referenced(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionExamenOcupacional::factory()->create([
            'commercial_client_id' => $payload['commercial_client_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'eps_code' => $payload['eps_code'],
            'eps_name' => 'EPS Test',
            'afp_code' => $payload['afp_code'],
            'afp_name' => 'AFP Test',
            'marital_status_code' => $payload['marital_status_code'],
            'marital_status_name' => 'Soltero',
            'solicitud_status_code' => $payload['solicitud_status_code'],
            'solicitud_status_name' => 'EN PROCESO',
        ]);

        $item = PayrollCatalogItem::query()
            ->ofType('marital_status')
            ->where('code', $payload['marital_status_code'])
            ->firstOrFail();

        $this->actingAs($editor)
            ->delete(route('gestion-humana.seleccion.catalogos.destroy', [
                'type' => 'marital_status',
                'item' => $item->id,
            ]))
            ->assertRedirect(route('gestion-humana.seleccion.catalogos', ['catalog' => 'marital_status']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_catalog_items', [
            'id' => $item->id,
            'code' => $payload['marital_status_code'],
        ]);
    }

    private function editorUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'is_active' => true,
            'area_key' => 'gestion_humana',
        ]);
        $user->givePermissionTo([
            'view.board.gestion_humana.seleccion',
            'seleccion.view',
            'seleccion.edit',
            'requisitions.selection_officer',
        ]);

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.seleccion',
            'seleccion.view',
        ]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(User $responsable): array
    {
        $city = PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'city', 'code' => 'CALI'],
            ['name' => 'Cali', 'is_active' => true, 'sort_order' => 1],
        );
        $position = PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'position', 'code' => 'VIG'],
            ['name' => 'Vigilante', 'is_active' => true, 'sort_order' => 1],
        );
        $eps = PayrollCatalogItem::query()->ofType('eps')->active()->first()
            ?? PayrollCatalogItem::query()->create([
                'catalog_type' => 'eps',
                'code' => 'SURA',
                'name' => 'Sura',
                'is_active' => true,
                'sort_order' => 1,
            ]);
        $afp = PayrollCatalogItem::query()->ofType('afp')->active()->first()
            ?? PayrollCatalogItem::query()->create([
                'catalog_type' => 'afp',
                'code' => 'PROTECCION',
                'name' => 'Protección',
                'is_active' => true,
                'sort_order' => 1,
            ]);
        $marital = PayrollCatalogItem::query()->ofType('marital_status')->active()->first()
            ?? PayrollCatalogItem::query()->create([
                'catalog_type' => 'marital_status',
                'code' => 'SOLTERO',
                'name' => 'Soltero',
                'is_active' => true,
                'sort_order' => 1,
            ]);
        $solicitud = PayrollCatalogItem::query()->ofType('seleccion_solicitud_status')->active()->first()
            ?? PayrollCatalogItem::query()->create([
                'catalog_type' => 'seleccion_solicitud_status',
                'code' => 'EN_PROCESO',
                'name' => 'EN PROCESO',
                'is_active' => true,
                'sort_order' => 1,
            ]);

        $client = CommercialClient::query()->firstOrCreate(
            ['nit' => '900555002-2'],
            ['name' => 'Cliente Examen Test', 'city' => 'Cali'],
        );

        return [
            'document_number' => '1234567890',
            'full_name' => 'Perez Juan',
            'position_code' => $position->code,
            'servicio_sector' => 'Vigilancia',
            'commercial_client_id' => $client->id,
            'eps_code' => $eps->code,
            'afp_code' => $afp->code,
            'birth_date' => '1990-05-15',
            'city_code' => $city->code,
            'address' => 'Calle 1 # 2-3',
            'email' => 'juan.examen@example.com',
            'phone' => '3001234567',
            'marital_status_code' => $marital->code,
            'fecha_arl' => '2026-09-22',
            'solicitud_status_code' => $solicitud->code,
            'responsable_user_id' => $responsable->id,
        ];
    }
}
