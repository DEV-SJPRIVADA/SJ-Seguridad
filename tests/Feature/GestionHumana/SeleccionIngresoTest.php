<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CommercialClient;
use App\Models\PayrollCatalogItem;
use App\Models\RequisitionUniform;
use App\Models\SeleccionIngreso;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeleccionIngresoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_store_creates_ingreso_with_required_fields(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.ingresos.store'), $payload)
            ->assertRedirect(route('gestion-humana.seleccion.ingresos'));

        $this->assertDatabaseHas('seleccion_ingresos', [
            'document_number' => $payload['document_number'],
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'city_code' => $payload['city_code'],
            'position_code' => $payload['position_code'],
            'blood_type_code' => $payload['blood_type_code'],
            'responsable_user_id' => $payload['responsable_user_id'],
        ]);

        $row = SeleccionIngreso::query()->where('document_number', $payload['document_number'])->first();
        $this->assertNotNull($row);
        $this->assertSame('Cali', $row->city_name);
        $this->assertSame('Vigilante', $row->position_name);
        $this->assertSame('O+', $row->blood_type_name);
    }

    public function test_store_duplicate_requires_confirm_flag(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionIngreso::factory()->create([
            'document_number' => $payload['document_number'],
            'commercial_client_id' => $payload['commercial_client_id'],
            'requisition_uniform_id' => $payload['requisition_uniform_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'blood_type_code' => $payload['blood_type_code'],
            'blood_type_name' => 'O+',
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.seleccion.ingresos.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confirm_duplicate', 'document_number']);

        $this->assertSame(1, SeleccionIngreso::query()->where('document_number', $payload['document_number'])->count());

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.ingresos.store'), [
                ...$payload,
                'confirm_duplicate' => 1,
                'full_name' => 'Segundo Registro',
            ])
            ->assertRedirect(route('gestion-humana.seleccion.ingresos'));

        $this->assertSame(2, SeleccionIngreso::query()->where('document_number', $payload['document_number'])->count());
    }

    public function test_update_and_destroy_ingreso(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        $ingreso = SeleccionIngreso::factory()->create([
            'document_number' => '111222333',
            'commercial_client_id' => $payload['commercial_client_id'],
            'requisition_uniform_id' => $payload['requisition_uniform_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'blood_type_code' => $payload['blood_type_code'],
            'blood_type_name' => 'O+',
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.seleccion.ingresos.update', $ingreso), [
                ...$payload,
                'document_number' => '111222333',
                'full_name' => 'Nombre Actualizado',
                'confirm_duplicate' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('seleccion_ingresos', [
            'id' => $ingreso->id,
            'full_name' => 'Nombre Actualizado',
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.seleccion.ingresos.destroy', $ingreso))
            ->assertRedirect(route('gestion-humana.seleccion.ingresos'));

        $this->assertDatabaseMissing('seleccion_ingresos', [
            'id' => $ingreso->id,
        ]);
    }

    public function test_datatable_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.seleccion.ingresos.datatable'))
            ->assertForbidden();

        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.ingresos.datatable', ['draw' => 1]))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_export_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.seleccion.ingresos.export'))
            ->assertForbidden();

        $viewer = $this->viewerUser();
        $payload = $this->validPayload($this->editorUser());

        SeleccionIngreso::factory()->create([
            'commercial_client_id' => $payload['commercial_client_id'],
            'requisition_uniform_id' => $payload['requisition_uniform_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'blood_type_code' => $payload['blood_type_code'],
            'blood_type_name' => 'O+',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.ingresos.export'))
            ->assertOk();
    }

    public function test_lookup_cedula_requires_edit_and_returns_matches(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionIngreso::factory()->create([
            'document_number' => '99887766',
            'commercial_client_id' => $payload['commercial_client_id'],
            'requisition_uniform_id' => $payload['requisition_uniform_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'blood_type_code' => $payload['blood_type_code'],
            'blood_type_name' => 'O+',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.ingresos.lookup-cedula', ['cedula' => '99887766']))
            ->assertForbidden();

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.seleccion.ingresos.lookup-cedula', ['cedula' => '99887766']))
            ->assertOk()
            ->assertJsonPath('has_duplicates', true)
            ->assertJsonCount(1, 'matches');
    }

    public function test_catalog_destroy_blocked_when_blood_type_referenced(): void
    {
        $editor = $this->editorUser();
        $payload = $this->validPayload($editor);

        SeleccionIngreso::factory()->create([
            'commercial_client_id' => $payload['commercial_client_id'],
            'requisition_uniform_id' => $payload['requisition_uniform_id'],
            'responsable_user_id' => $payload['responsable_user_id'],
            'city_code' => $payload['city_code'],
            'city_name' => 'Cali',
            'position_code' => $payload['position_code'],
            'position_name' => 'Vigilante',
            'blood_type_code' => 'O+',
            'blood_type_name' => 'O+',
        ]);

        $item = PayrollCatalogItem::query()
            ->ofType('blood_type')
            ->where('code', 'O+')
            ->firstOrFail();

        $this->actingAs($editor)
            ->delete(route('gestion-humana.seleccion.catalogos.destroy', [
                'type' => 'blood_type',
                'item' => $item->id,
            ]))
            ->assertRedirect(route('gestion-humana.seleccion.catalogos', ['catalog' => 'blood_type']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_catalog_items', [
            'id' => $item->id,
            'code' => 'O+',
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
        $blood = PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'blood_type', 'code' => 'O+'],
            ['name' => 'O+', 'is_active' => true, 'sort_order' => 1],
        );

        $client = CommercialClient::query()->firstOrCreate(
            ['nit' => '900555001-1'],
            ['name' => 'Cliente Ingreso Test', 'city' => 'Cali'],
        );

        $uniform = RequisitionUniform::query()->where('is_active', true)->first()
            ?? RequisitionUniform::query()->create([
                'name' => 'Dotación test ingreso',
                'is_active' => true,
                'sort_order' => 99,
            ]);

        return [
            'document_number' => '1234567890',
            'full_name' => 'Perez Juan',
            'email' => 'juan.perez@example.com',
            'phone' => '3001234567',
            'city_code' => $city->code,
            'position_code' => $position->code,
            'commercial_client_id' => $client->id,
            'shirt_size' => 'M',
            'pants_size' => '32',
            'shoes_size' => '40',
            'requisition_uniform_id' => $uniform->id,
            'fecha_ingreso' => '2026-09-22',
            'blood_type_code' => $blood->code,
            'reemplaza_a' => 'N/A',
            'responsable_user_id' => $responsable->id,
            'jefe_ope' => 'Jefe Operaciones',
        ];
    }
}
