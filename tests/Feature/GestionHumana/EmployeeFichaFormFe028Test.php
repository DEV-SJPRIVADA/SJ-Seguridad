<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\EmployeeFichaMasivosPayload;
use Tests\TestCase;

class EmployeeFichaFormFe028Test extends TestCase
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

    public function test_create_form_renders_masivos_catalog_sections(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $response = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'));

        $response->assertOk()
            ->assertSee('Contrato y nómina', false)
            ->assertSee('Centros', false)
            ->assertSee('Pagos', false)
            ->assertSee('Nómina avanzada', false)
            ->assertSee('id="payment_method_code"', false)
            ->assertSee('name="payroll_extra[ccf_code]"', false)
            ->assertSee('name="payroll_extra[work_center_code]"', false)
            ->assertSee('C — Cedula de ciudadania', false)
            ->assertSee('CE — Cedula de extranjeria', false);
    }

    public function test_edit_ficha_form_renders_required_payment_and_ccf_selectors(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '803333333',
            'hired_full_name' => 'Formulario Completo Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '803333333',
            'full_name' => 'Formulario Completo Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->assertSee('id="bank_code"', false)
            ->assertSee('id="account_type"', false)
            ->assertSee('id="payroll_extra_ccf_code"', false)
            ->assertDontSee('name="eps_name"', false)
            ->assertDontSee('name="compensation_fund_name"', false);
    }

    public function test_update_ficha_persists_payroll_extra_fields_from_form(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '804444444',
            'hired_full_name' => 'Persistencia Extra Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '804444444',
            'full_name' => 'Persistencia Extra Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'payroll_extra' => ['military_book' => 'LM-OLD'],
        ]);

        $payload = $this->masivosCorePayload([
            'payroll_extra' => [
                'ccf_code' => 'CCF01',
                'work_center_code' => 'WC01',
                'workday' => '1',
                'military_book' => 'LM-NEW',
            ],
        ]);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.employees.ficha.update', $entry), $payload)
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $profile = $entry->profile->fresh();

        $this->assertSame('WC01', $profile->payrollExtraValue('work_center_code'));
        $this->assertSame('1', $profile->payrollExtraValue('workday'));
        $this->assertSame('LM-NEW', $profile->payrollExtraValue('military_book'));
        $this->assertSame('Centro Trabajo Test', $profile->work_center_name);
        $this->assertSame('Caja Compensacion Test', $profile->compensation_fund_name);
    }
}
