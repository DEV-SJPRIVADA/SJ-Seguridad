<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Services\GestionHumana\EmployeeFichaProfileCatalogSync;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\EmployeeFichaMasivosPayload;
use Tests\TestCase;

class EmployeeFichaCatalogSyncFe028Test extends TestCase
{
    use EmployeeFichaMasivosPayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        $this->seedFe028CatalogFixtures();
    }

    public function test_catalog_sync_fills_profile_and_payroll_extra_names_from_codes(): void
    {
        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '1234567890',
            'full_name' => 'Perez Garcia Juan Carlos',
            'eps_code' => 'EPS01',
            'afp_code' => 'AFP01',
            'position_code' => 'VIG',
            'cost_center_code' => 'CC01',
            'bank_code' => 'B01',
            'salary_type_code' => '1',
            'contract_type_code' => 'IND',
            'residence_city_code' => '11001',
            'payroll_extra' => [
                'work_center_code' => 'WC01',
                'ccf_code' => 'CCF01',
                'branch_code' => 'S01',
            ],
        ]);

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'salary_type', 'code' => '1'],
            ['name' => 'Fijo', 'sort_order' => 1, 'is_active' => true],
        );
        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'contract_type', 'code' => 'IND'],
            ['name' => 'Indefinido', 'sort_order' => 1, 'is_active' => true],
        );
        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'city', 'code' => '11001'],
            ['name' => 'Bogota', 'sort_order' => 1, 'is_active' => true],
        );
        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'branch', 'code' => 'S01'],
            ['name' => 'Sucursal Norte', 'sort_order' => 1, 'is_active' => true],
        );

        app(EmployeeFichaProfileCatalogSync::class)->syncAndSave($profile);

        $profile->refresh();

        $this->assertSame('EPS Test', $profile->eps_name);
        $this->assertSame('AFP Test', $profile->afp_name);
        $this->assertSame('Vigilante', $profile->position_name);
        $this->assertSame('Centro Costo Test', $profile->cost_center_name);
        $this->assertSame('Banco Test', $profile->bank_name);
        $this->assertSame('Centro Trabajo Test', $profile->work_center_name);
        $this->assertSame('Caja Compensacion Test', $profile->compensation_fund_name);
        $this->assertSame('Sucursal Norte', $profile->payrollExtraValue('branch_name'));
        $this->assertSame('Perez', $profile->first_surname);
        $this->assertSame('Juan', $profile->first_name);
    }

    public function test_catalog_sync_normalizes_document_type_code_from_select_label(): void
    {
        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '99887766',
            'full_name' => 'Test User',
            'document_type' => 'CE — Cedula de extranjeria',
        ]);

        app(EmployeeFichaProfileCatalogSync::class)->syncAndSave($profile);

        $this->assertSame('CE', $profile->fresh()->document_type);
    }

    public function test_store_manual_employee_requires_masivos_core_fields(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), [
                'hired_document' => '801111111',
                'hired_full_name' => 'Empleado Sin Campos',
            ])
            ->assertSessionHasErrors([
                'sex',
                'hire_date',
                'position_code',
                'salary',
                'cost_center_code',
                'eps_code',
                'afp_code',
                'bank_code',
                'account_type',
                'account_number',
                'payment_method_code',
                'payroll_extra.ccf_code',
            ]);
    }

    public function test_update_ficha_syncs_catalog_names_when_core_payload_is_valid(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '802222222',
            'hired_full_name' => 'Sync Update Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '802222222',
            'full_name' => 'Sync Update Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $payload = $this->masivosCorePayload([
            'eps_code' => 'EPS01',
            'position_code' => 'VIG',
        ]);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.employees.ficha.update', $entry), $payload)
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $profile = $entry->profile->fresh();

        $this->assertSame('EPS Test', $profile->eps_name);
        $this->assertSame('Vigilante', $profile->position_name);
        $this->assertSame('Caja Compensacion Test', $profile->compensation_fund_name);
    }
}
