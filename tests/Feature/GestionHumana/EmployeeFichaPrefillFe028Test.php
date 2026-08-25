<?php

namespace Tests\Feature\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionPositionPayrollMap;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFichaPrefillFe028Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_build_for_entry_does_not_prefill_exportable_catalog_fields_from_requisition(): void
    {
        $entry = $this->createPendingEntry([
            'cost_center' => 'CC-REQ-TEXTO',
        ]);

        $profile = app(EmployeeFichaProfilePrefill::class)->buildForEntry($entry);

        $this->assertNull($profile->cost_center_code);
        $this->assertNull($profile->cost_center_name);
        $this->assertNull($profile->residence_city_name);
        $this->assertNull($profile->work_center_name);
        $this->assertNull($profile->contract_type_name);
        $this->assertNotEmpty($profile->work_city_name);
    }

    public function test_build_for_entry_prefills_work_city_from_requisition_city(): void
    {
        $city = RequisitionCity::query()->firstOrFail();
        $entry = $this->createPendingEntry();

        $this->assertSame($city->id, $entry->requisition?->city_id);

        $profile = app(EmployeeFichaProfilePrefill::class)->buildForEntry($entry);

        $this->assertSame($city->name, $profile->work_city_name);
        $this->assertNotEmpty($profile->work_city_code);
        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'city',
            'code' => $profile->work_city_code,
            'name' => $profile->work_city_name,
        ]);
    }

    public function test_build_for_entry_still_suggests_salary_hire_date_and_mapped_position(): void
    {
        $position = RequisitionPosition::query()->firstOrFail();
        RequisitionPositionPayrollMap::query()->updateOrCreate(
            ['requisition_position_id' => $position->id],
            ['payroll_position_code' => 'VIG-PREFILL'],
        );

        $entry = $this->createPendingEntry([
            'base_salary' => 3200000,
            'position_id' => $position->id,
        ]);

        $profile = app(EmployeeFichaProfilePrefill::class)->buildForEntry($entry);

        $this->assertSame('3200000.00', $profile->salary);
        $this->assertNotNull($profile->hire_date);
        $this->assertSame('VIG-PREFILL', $profile->position_code);
        $this->assertSame($position->name, $profile->position_name);
        $this->assertSame('M', $profile->sex);
    }

    public function test_requisition_reference_includes_hints_without_persisting_them(): void
    {
        $entry = $this->createPendingEntry([
            'cost_center' => 'CC-HINT-ONLY',
            'base_salary' => 1800000,
        ]);

        $reference = app(EmployeeFichaProfilePrefill::class)->requisitionReferenceForEntry($entry);

        $this->assertNotNull($reference);
        $this->assertSame('CC-HINT-ONLY', $reference['cost_center_hint']);
        $this->assertSame('1800000.00', $reference['base_salary']);
        $this->assertNotEmpty($reference['city_name']);
        $this->assertNotEmpty($reference['client_name']);
    }

    public function test_create_form_shows_reference_block_and_leaves_cost_center_selector_empty(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = $this->createPendingEntry([
            'cost_center' => 'CC-UI-HINT',
            'base_salary' => 2100000,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]))
            ->assertOk()
            ->assertSee('Referencia de requisición', false)
            ->assertSee('CC-UI-HINT', false)
            ->assertSee('Centro de costo (texto requisición)', false)
            ->assertSee('id="cost_center_code"', false)
            ->assertSee('Ciudad de trabajo', false)
            ->assertSee('id="work_city_code"', false);
    }

    public function test_store_from_pending_opens_period_with_synced_catalog_names(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->seedFe028CatalogFixturesForPrefill();

        $entry = $this->createPendingEntry();

        $payload = [
            'ficha_entry_id' => $entry->id,
            'hired_document' => $entry->hired_document,
            'hired_full_name' => $entry->hired_full_name,
            'sex' => 'M',
            'hire_date' => now()->subMonth()->toDateString(),
            'position_code' => 'VIG',
            'salary' => '2500000',
            'cost_center_code' => 'CC01',
            'eps_code' => 'EPS01',
            'afp_code' => 'AFP01',
            'bank_code' => 'B01',
            'account_type' => '1',
            'account_number' => '1234567890',
            'payment_method_code' => '1',
            'payroll_extra' => ['ccf_code' => 'CCF01'],
        ];

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), $payload)
            ->assertRedirect();

        $period = $entry->fresh()->activeEmploymentPeriod;

        $this->assertNotNull($period);
        $this->assertSame('Centro Costo Test', $period->cost_center_name);
        $this->assertSame('EPS Test', $period->eps_name);
    }

    public function test_store_from_pending_persists_work_city_from_requisition_city_id_when_not_posted(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->seedFe028CatalogFixturesForPrefill();

        $city = RequisitionCity::query()->firstOrFail();
        $city->update(['name' => 'CALI TRABAJO TEST']);

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'city', 'code' => '76099'],
            ['name' => 'CALI TRABAJO TEST', 'sort_order' => 1, 'is_active' => true],
        );

        $entry = $this->createPendingEntry(['city_id' => $city->id]);

        $payload = [
            'ficha_entry_id' => $entry->id,
            'hired_document' => $entry->hired_document,
            'hired_full_name' => $entry->hired_full_name,
            'sex' => 'M',
            'hire_date' => now()->subMonth()->toDateString(),
            'position_code' => 'VIG',
            'salary' => '2500000',
            'cost_center_code' => 'CC01',
            'eps_code' => 'EPS01',
            'afp_code' => 'AFP01',
            'bank_code' => 'B01',
            'account_type' => '1',
            'account_number' => '1234567890',
            'payment_method_code' => '1',
            'payroll_extra' => ['ccf_code' => 'CCF01'],
            // work_city_code intentionally omitted — must come from requisition.city_id
        ];

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), $payload)
            ->assertRedirect();

        $profile = $entry->fresh('profile')->profile;

        $this->assertNotNull($profile);
        $this->assertSame('76099', $profile->work_city_code);
        $this->assertSame('CALI TRABAJO TEST', $profile->work_city_name);
    }

    /**
     * @param  array<string, mixed>  $requisitionOverrides
     */
    private function createPendingEntry(array $requisitionOverrides = []): PersonalRequisitionFichaEntry
    {
        $requester = User::factory()->create(['must_change_password' => false]);
        $code = 'REQ-PREFILL-'.uniqid();

        $requisition = PersonalRequisition::query()->create(array_merge([
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
            'cost_center' => 'CC-REQ-DEFAULT',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
            'hiring_date' => now()->subWeek()->toDateString(),
            'base_salary' => 1500000,
        ], $requisitionOverrides));

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '77'.random_int(10000000, 99999999),
            'hired_full_name' => 'Prefill FE028 Test',
        ]);
    }

    private function seedFe028CatalogFixturesForPrefill(): void
    {
        foreach ([
            ['position', 'VIG', 'Vigilante'],
            ['cost_center', 'CC01', 'Centro Costo Test'],
            ['eps', 'EPS01', 'EPS Test'],
            ['afp', 'AFP01', 'AFP Test'],
            ['ccf', 'CCF01', 'Caja Test'],
            ['bank', 'B01', 'Banco Test'],
            ['payment_method', '1', 'Transferencia'],
        ] as [$type, $code, $name]) {
            PayrollCatalogItem::query()->firstOrCreate(
                ['catalog_type' => $type, 'code' => $code],
                ['name' => $name, 'sort_order' => 1, 'is_active' => true],
            );
        }
    }
}
