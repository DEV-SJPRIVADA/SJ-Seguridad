<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFichaSalaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_edit_ficha_form_does_not_nest_letter_form_inside_patch_form(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createDesvinculadoEntry($manager);

        $response = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $response->assertOk();

        $html = $response->getContent();
        $mainFormStart = strpos($html, 'id="ficha-empleados-form"');
        $mainFormEnd = strpos($html, '</form>', $mainFormStart);

        $this->assertNotFalse($mainFormStart);
        $this->assertNotFalse($mainFormEnd);

        $mainFormChunk = substr($html, $mainFormStart, $mainFormEnd - $mainFormStart);
        $this->assertStringNotContainsString('<form', $mainFormChunk);
        $this->assertStringContainsString('Generar cartas', $html);
    }

    public function test_update_ficha_normalizes_formatted_salary_without_corrupting_value(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createInFichaEntry();

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.employees.ficha.update', $entry), [
                'salary' => '2.180.000',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $entry->profile->refresh();

        $this->assertSame('2180000.00', $entry->profile->salary);
    }

    public function test_update_ficha_rejects_salary_above_decimal_column_limit(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createInFichaEntry();

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.employees.ficha.update', $entry), [
                'salary' => '2180000000000',
            ])
            ->assertSessionHasErrors('salary');

        $this->assertNull($entry->profile->fresh()->salary);
    }

    private function managerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        return $user;
    }

    private function createDesvinculadoEntry(User $terminator): PersonalRequisitionFichaEntry
    {
        $entry = $this->createInFichaEntry(withActivePeriod: true);

        EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->update([
                'salary' => 2180000,
                'contract_type_name' => 'Termino indefinido',
            ]);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        return $entry->fresh(['profile']);
    }

    private function createInFichaEntry(bool $withActivePeriod = false): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-SALARY-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '10'.random_int(10000000, 99999999),
            'hired_full_name' => 'Empleado Salario Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'position_name' => 'Vigilante',
        ]);

        if ($withActivePeriod) {
            EmployeeFichaEmploymentPeriod::query()->create([
                'personal_requisition_ficha_entry_id' => $entry->id,
                'personal_requisition_id' => $requisition->id,
                'sequence' => 1,
                'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
                'hire_date' => now()->subMonth()->toDateString(),
                'position_name' => 'Vigilante',
                'salary' => 1500000,
                'opened_by' => $mover->id,
            ]);
        }

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'termination_cause', 'code' => 'RENUNCIA'],
            ['name' => 'Renuncia voluntaria', 'sort_order' => 1, 'is_active' => true],
        );

        return $entry->fresh(['profile']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRequisition(string $code, array $overrides = []): PersonalRequisition
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisition::query()->create(array_merge([
            'code' => $code,
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'gestion_humana',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil de prueba.',
            'service_structure' => 'Turno de prueba.',
            'cost_center' => 'CC-FICHA',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
