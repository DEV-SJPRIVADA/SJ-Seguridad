<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
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
            ->assertSee('C — Cédula de ciudadanía', false)
            ->assertSee('CE — Cédula de extranjería', false);
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

    public function test_create_form_shows_required_birth_place_field(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertOk()
            ->assertSee('id="birth_place"', false)
            ->assertSee('Lugar de nacimiento', false);
    }

    public function test_create_form_shows_archive_section_with_required_box(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertOk()
            ->assertSee('>Archivo</h3>', false)
            ->assertSee('id="archive_shelf"', false)
            ->assertSee('id="archive_box"', false)
            ->assertSee('name="archive_box"', false);
    }

    public function test_edit_ficha_form_does_not_show_archive_section(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809999001',
            'hired_full_name' => 'Sin Seccion Archivo',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809999001',
            'full_name' => 'Sin Seccion Archivo',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->assertDontSee('id="archive_box"', false)
            ->assertDontSee('name="archive_shelf"', false);
    }

    public function test_store_requires_archive_box_and_persists_archive_location(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $missingBox = $this->masivosCorePayload([
            'hired_document' => '809999002',
            'hired_full_name' => 'Sin Caja Archivo',
            'archive_box' => '',
            'archive_shelf' => 'E-3',
        ]);

        $this->actingAs($manager)
            ->from(route('gestion-humana.ficha-empleados.employees.create'))
            ->post(route('gestion-humana.ficha-empleados.employees.store'), $missingBox)
            ->assertSessionHasErrors('archive_box');

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), $this->masivosCorePayload([
                'hired_document' => '809999003',
                'hired_full_name' => 'Con Caja Archivo',
                'archive_shelf' => 'Estante A',
                'archive_box' => 'Caja 12',
            ]))
            ->assertRedirect();

        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', '809999003')
            ->firstOrFail();

        $this->assertSame('ESTANTE A', $entry->profile?->archive_shelf);
        $this->assertSame('CAJA 12', $entry->profile?->archive_box);
    }

    public function test_store_requires_birth_place(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $payload = $this->masivosCorePayload([
            'hired_document' => '807777777',
            'hired_full_name' => 'Sin Lugar Nacimiento',
            'birth_place' => '',
        ]);

        $this->actingAs($manager)
            ->from(route('gestion-humana.ficha-empleados.employees.create'))
            ->post(route('gestion-humana.ficha-empleados.employees.store'), $payload)
            ->assertSessionHasErrors('birth_place')
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.create'));

        $this->actingAs($manager)
            ->followingRedirects()
            ->from(route('gestion-humana.ficha-empleados.employees.create'))
            ->post(route('gestion-humana.ficha-empleados.employees.store'), $payload)
            ->assertOk()
            ->assertSee('No se pudo guardar. Corrija los siguientes errores:', false)
            ->assertSee('lugar de nacimiento', false);
    }

    public function test_create_desde_missing_pending_redirects_with_message(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => 999999]))
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'pendientes']))
            ->assertSessionHasErrors('form');
    }

    public function test_update_persists_birth_place(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '808888888',
            'hired_full_name' => 'Lugar Nacimiento Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '808888888',
            'full_name' => 'Lugar Nacimiento Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($manager)
            ->patch(
                route('gestion-humana.ficha-empleados.employees.ficha.update', $entry),
                $this->masivosCorePayload(['birth_place' => 'Medellin']),
            )
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $this->assertSame('Medellin', $entry->profile->fresh()->birth_place);
    }

    public function test_ficha_forms_disable_native_validation_to_show_visible_errors(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertOk()
            ->assertSee('novalidate', false)
            ->assertSee('fichaEmpleadosValidateForm', false);
    }

    public function test_create_form_allows_managing_all_requirement_flags(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertOk()
            ->assertSee('name="requires_courses"', false)
            ->assertSee('name="requires_acreditacion"', false)
            ->assertSee('name="requires_psicofisicos"', false)
            ->assertDontSee('data-lock-disabled="1"', false);
    }

    public function test_edit_ficha_allows_managing_all_requirement_flags(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809000001',
            'hired_full_name' => 'Flags Editables Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809000001',
            'full_name' => 'Flags Editables Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->assertSee('name="requires_courses"', false)
            ->assertSee('name="requires_acreditacion"', false)
            ->assertSee('name="requires_psicofisicos"', false)
            ->assertDontSee('data-lock-disabled="1"', false);
    }

    public function test_update_ficha_persists_requirement_flags_with_manage_permission(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809000002',
            'hired_full_name' => 'Persistencia Flags Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809000002',
            'full_name' => 'Persistencia Flags Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_courses' => true,
            'requires_acreditacion' => true,
            'requires_psicofisicos' => true,
        ]);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.employees.ficha.update', $entry), $this->masivosCorePayload([
                'requires_courses' => '0',
                'requires_acreditacion' => '0',
                'requires_psicofisicos' => '0',
            ]))
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $profile->refresh();

        $this->assertFalse((bool) $profile->requires_courses);
        $this->assertFalse((bool) $profile->requires_acreditacion);
        $this->assertFalse((bool) $profile->requires_psicofisicos);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.employees.ficha.update', $entry), $this->masivosCorePayload([
                'requires_psicofisicos' => '1',
            ]))
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $this->assertTrue((bool) $profile->fresh()->requires_psicofisicos);
    }

    public function test_rehire_form_allows_editing_identity_fields_except_document(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809000003',
            'hired_full_name' => 'Reingreso Identidad Test',
            'first_surname' => 'Reingreso',
            'first_name' => 'Identidad',
            'birth_place' => 'Cali',
            'sex' => 'M',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809000003',
            'full_name' => 'Reingreso Identidad Test',
            'first_surname' => 'Reingreso',
            'first_name' => 'Identidad',
            'birth_place' => 'Cali',
            'sex' => 'M',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => now()->subYear()->toDateString(),
            'termination_date' => now()->subMonth()->toDateString(),
            'is_rehireable' => true,
            'opened_by' => $manager->id,
            'closed_by' => $manager->id,
        ]);

        $profile->update([
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => now()->subMonth()->toDateString(),
        ]);

        $entry->update(['moved_to_ficha_at' => null, 'moved_to_ficha_by' => null]);
        $this->assertTrue($entry->refresh()->isRehirePending());

        $html = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]))
            ->assertOk()
            ->assertSee('Reingreso — Reingreso Identidad Test', false)
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/id="first_surname"[^>]*\breadonly\b/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="birth_place"[^>]*\breadonly\b/', $html);

        // Selects de identificación (género, ciudad de expedición) habilitados en reingreso.
        $formStart = strpos($html, 'id="ficha-empleados-form"');
        $this->assertNotFalse($formStart);
        $formHtml = substr($html, $formStart);
        $this->assertStringNotContainsString('disabled: true', $formHtml);

        // La cédula sigue siendo la llave del histórico: solo lectura en reingreso.
        $this->assertMatchesRegularExpression('/id="hired_document"[^>]*\breadonly\b/', $html);
    }

    public function test_requisition_reference_block_is_informational_not_editable_inputs(): void
    {
        $html = view('areas.gestion_humana.ficha-empleados.partials.ficha-requisition-reference', [
            'reference' => [
                'code' => 'RQ-TEST-01',
                'client_name' => 'Cliente Test',
                'position_name' => 'Vigilante',
                'contract_type_name' => 'Fijo',
                'base_salary' => 2100000,
                'hiring_date' => '2026-01-15',
                'cost_center_hint' => 'CC-HINT',
                'city_name' => 'Bogotá',
            ],
        ])->render();

        $this->assertStringContainsString('solo consulta', $html);
        $this->assertStringContainsString('RQ-TEST-01', $html);
        $this->assertStringContainsString('Centro de costo (texto requisición)', $html);
        // Se muestra como panel de consulta legible: badge, fecha y salario formateados.
        $this->assertStringContainsString('Solo consulta', $html);
        $this->assertStringContainsString('15/01/2026', $html);
        $this->assertStringContainsString('$ 2.100.000', $html);
        $this->assertStringContainsString('ficha-empleados-form__reference-grid', $html);
        // Es un bloque informativo: ningún campo del bloque debe ser un input.
        $this->assertStringNotContainsString('<input', $html);
        $this->assertStringNotContainsString('readonly', $html);
    }
}
