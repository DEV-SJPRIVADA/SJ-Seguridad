<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            ->assertSee('id="payroll_extra_contributor_type"', false)
            ->assertSee('Dependiente', false)
            ->assertSee('Aprendices en Etapa productiva', false)
            ->assertSee('Excluir auxilio de transporte', false)
            ->assertSee('id="payroll_extra_exclude_transport_allowance"', false)
            ->assertDontSee('Excluir horas extra', false)
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

        $html = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->assertSee('id="bank_code"', false)
            ->assertSee('id="account_type"', false)
            ->assertSee('id="payroll_extra_ccf_code"', false)
            ->assertSee('id="termination_date"', false)
            ->assertSee('Fecha desvinculación', false)
            ->assertSee('Habilitar edición', false)
            ->assertSee('ficha-empleados-form--readonly', false)
            ->assertSee('ficha-empleados-form__fields', false)
            ->assertSee('x-ref="fichaFields"', false)
            ->assertSee('syncFieldLock', false)
            ->assertDontSee('name="eps_name"', false)
            ->assertDontSee('name="compensation_fund_name"', false)
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/id="first_surname"[^>]*\breadonly\b/', $html);
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

    public function test_update_ficha_persists_termination_date_and_syncs_status(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '805555555',
            'hired_full_name' => 'Fecha Retiro Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '805555555',
            'full_name' => 'Fecha Retiro Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
        ]);

        $terminationDate = now()->subDay()->toDateString();

        $this->actingAs($manager)
            ->patch(
                route('gestion-humana.ficha-empleados.employees.ficha.update', $entry),
                $this->masivosCorePayload(['termination_date' => $terminationDate]),
            )
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $profile = $entry->profile->fresh();

        $this->assertSame($terminationDate, $profile->termination_date?->toDateString());
        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $profile->employment_status);

        $this->actingAs($manager)
            ->patch(
                route('gestion-humana.ficha-empleados.employees.ficha.update', $entry),
                $this->masivosCorePayload(['termination_date' => null]),
            )
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $profile = $entry->profile->fresh();

        $this->assertNull($profile->termination_date);
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $profile->employment_status);
    }

    public function test_age_from_birth_date_uses_completed_years(): void
    {
        $this->assertSame(
            31,
            EmployeeFichaProfile::ageFromBirthDate('1995-03-15', Carbon::parse('2026-03-15')),
        );
        $this->assertSame(
            30,
            EmployeeFichaProfile::ageFromBirthDate('1995-03-15', Carbon::parse('2026-03-14')),
        );
        $this->assertNull(EmployeeFichaProfile::ageFromBirthDate(null));
        $this->assertNull(EmployeeFichaProfile::ageFromBirthDate('2099-01-01', Carbon::parse('2026-01-01')));
    }

    public function test_create_form_wires_birth_date_to_age_field(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertOk()
            ->assertSee('id="birth_date"', false)
            ->assertSee('id="payroll_extra_age"', false)
            ->assertSee('calculateAgeFromBirthDate', false);
    }

    public function test_update_ficha_recalculates_age_from_birth_date(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '806666666',
            'hired_full_name' => 'Edad Automatica Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '806666666',
            'full_name' => 'Edad Automatica Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'payroll_extra' => ['age' => 99, 'military_book' => 'LM-KEEP'],
        ]);

        $birthDate = now()->subYears(34)->subMonths(2)->toDateString();
        $expectedAge = EmployeeFichaProfile::ageFromBirthDate($birthDate);

        $this->actingAs($manager)
            ->patch(
                route('gestion-humana.ficha-empleados.employees.ficha.update', $entry),
                $this->masivosCorePayload([
                    'birth_date' => $birthDate,
                    'payroll_extra' => [
                        'ccf_code' => 'CCF01',
                        'age' => 12,
                        'military_book' => 'LM-KEEP',
                    ],
                ]),
            )
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $profile = $entry->profile->fresh();

        $this->assertSame($birthDate, $profile->birth_date?->toDateString());
        $this->assertSame($expectedAge, (int) $profile->age);
        $this->assertSame($expectedAge, (int) $profile->payrollExtraValue('age'));
        $this->assertSame('LM-KEEP', $profile->payrollExtraValue('military_book'));
    }
}
