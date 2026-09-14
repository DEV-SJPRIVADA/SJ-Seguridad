<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\EmployeeTerminationFollowup;
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
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterPackGeneratorService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class DesvinculacionesMasivosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Storage::fake('local');
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_lookup_returns_active_employee_name(): void
    {
        $user = $this->masivosUser();
        $entry = $this->createActiveEntry('Empleado Activo Lookup');

        $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.lookup'), [
                'document_number' => $entry->hired_document,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'document_number' => $entry->hired_document,
                'full_name' => 'Empleado Activo Lookup',
                'ficha_entry_id' => $entry->id,
            ]);
    }

    public function test_lookup_rejects_missing_or_inactive_document(): void
    {
        $user = $this->masivosUser();
        $active = $this->createActiveEntry('Activo');
        $this->terminateViaFicha($active);

        $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.lookup'), [
                'document_number' => $active->hired_document,
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.lookup'), [
                'document_number' => '9999999999',
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_process_without_masivos_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $entry = $this->createActiveEntry();
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.process'), [
                'rows' => [[
                    'document_number' => $entry->hired_document,
                    'termination_date' => now()->toDateString(),
                    'template_id' => $templates[0]->id,
                    'signatory_id' => $signatory->id,
                ]],
            ])
            ->assertForbidden();
    }

    public function test_process_partial_batch_continues_and_creates_followup(): void
    {
        $user = $this->masivosUser();
        $okEntry = $this->createActiveEntry('OK Empleado');
        $already = $this->createActiveEntry('Ya Desvinculado');
        $this->terminateViaFicha($already);

        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();
        $date = now()->toDateString();

        $response = $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.process'), [
                'rows' => [
                    [
                        'document_number' => $okEntry->hired_document,
                        'termination_date' => $date,
                        'template_id' => $templates[0]->id,
                        'signatory_id' => $signatory->id,
                        'termination_cause_code' => 'RENUNCIA',
                        'is_rehireable' => true,
                    ],
                    [
                        'document_number' => $already->hired_document,
                        'termination_date' => $date,
                        'template_id' => $templates[0]->id,
                        'signatory_id' => $signatory->id,
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.ok', 1);
        $this->assertGreaterThanOrEqual(1, $response->json('summary.failed'));

        $okEntry->refresh()->load('profile');
        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $okEntry->profile->employment_status);

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $okEntry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $this->assertSame($date, $period->last_work_day?->toDateString());
        $this->assertSame($date, $period->termination_date?->toDateString());

        $this->assertDatabaseHas('employee_termination_followups', [
            'employee_ficha_employment_period_id' => $period->id,
            'document_number' => $okEntry->hired_document,
        ]);

        $this->assertNotNull(
            AuditLog::query()
                ->where('module', 'desvinculaciones')
                ->where('event_type', 'bulk_termination')
                ->where('action', 'process')
                ->first()
        );
    }

    public function test_letter_failure_leaves_terminated_without_letter_flag(): void
    {
        $user = $this->masivosUser();
        $entry = $this->createActiveEntry();
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $this->mock(TerminationLetterPackGeneratorService::class, function ($mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(ValidationException::withMessages([
                    'template_ids' => 'Fallo forzado de carta.',
                ]));
        });

        $response = $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.process'), [
                'rows' => [[
                    'document_number' => $entry->hired_document,
                    'termination_date' => now()->toDateString(),
                    'template_id' => $templates[0]->id,
                    'signatory_id' => $signatory->id,
                ]],
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.ok', 1);
        $response->assertJsonFragment(['type' => 'letter']);

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $followup = EmployeeTerminationFollowup::query()
            ->where('employee_ficha_employment_period_id', $period->id)
            ->firstOrFail();

        $this->assertFalse($followup->letter_generated);
        $this->assertNull($response->json('download_token'));
    }

    public function test_successful_letters_return_zip_download_token(): void
    {
        $user = $this->masivosUser();
        $entryA = $this->createActiveEntry('Empleado A');
        $entryB = $this->createActiveEntry('Empleado B');
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();
        $date = now()->toDateString();

        $response = $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.process'), [
                'rows' => [
                    [
                        'document_number' => $entryA->hired_document,
                        'termination_date' => $date,
                        'template_id' => $templates[0]->id,
                        'signatory_id' => $signatory->id,
                    ],
                    [
                        'document_number' => $entryB->hired_document,
                        'termination_date' => $date,
                        'template_id' => $templates[0]->id,
                        'signatory_id' => $signatory->id,
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.letters', 2);
        $token = $response->json('download_token');
        $this->assertNotEmpty($token);
        $downloadUrl = $response->json('download_url');
        $this->assertNotEmpty($downloadUrl);

        $cached = Cache::get('desvinculaciones.bulk_zip.'.$token);
        $this->assertIsArray($cached);
        $this->assertFileExists($cached['path']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($cached['path']) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();

        $this->actingAs($user)
            ->get($downloadUrl)
            ->assertOk()
            ->assertDownload();

        $this->assertTrue(
            EmployeeTerminationFollowup::query()
                ->where('document_number', $entryA->hired_document)
                ->where('letter_generated', true)
                ->exists()
        );
    }

    public function test_ficha_terminate_creates_followup(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $this->assertDatabaseHas('employee_termination_followups', [
            'employee_ficha_employment_period_id' => $period->id,
            'letter_generated' => 0,
        ]);
    }

    public function test_letter_generate_marks_followup_letter_generated(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry();
        $this->terminateViaFicha($entry, $terminator);

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $followup = EmployeeTerminationFollowup::query()
            ->where('employee_ficha_employment_period_id', $period->id)
            ->firstOrFail();
        $this->assertFalse($followup->letter_generated);

        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk();

        $followup->refresh();
        $this->assertTrue($followup->letter_generated);
    }

    public function test_duplicate_document_in_batch_is_rejected(): void
    {
        $user = $this->masivosUser();
        $entry = $this->createActiveEntry();
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();
        $date = now()->toDateString();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.desvinculaciones.masivos.process'), [
                'rows' => [
                    [
                        'document_number' => $entry->hired_document,
                        'termination_date' => $date,
                        'template_id' => $templates[0]->id,
                        'signatory_id' => $signatory->id,
                    ],
                    [
                        'document_number' => $entry->hired_document,
                        'termination_date' => $date,
                        'template_id' => $templates[0]->id,
                        'signatory_id' => $signatory->id,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows.1.document_number']);
    }

    public function test_followup_factory_for_period_is_reusable(): void
    {
        $entry = $this->createActiveEntry();
        $this->terminateViaFicha($entry);
        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        EmployeeTerminationFollowup::query()
            ->where('employee_ficha_employment_period_id', $period->id)
            ->delete();

        $followup = EmployeeTerminationFollowup::factory()
            ->forPeriod($period, [
                'document_number' => $entry->hired_document,
                'full_name' => $entry->hired_full_name,
            ])
            ->letterGenerated()
            ->create();

        $this->assertTrue($followup->letter_generated);
        $this->assertSame($period->id, $followup->employee_ficha_employment_period_id);
    }

    private function masivosUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'desvinculaciones.view',
            'desvinculaciones.masivos',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        return $user;
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        return $user;
    }

    private function terminateViaFicha(PersonalRequisitionFichaEntry $entry, ?User $terminator = null): void
    {
        $terminator ??= $this->terminatorUser();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();
    }

    private function seedSignatory(): PayrollCatalogItem
    {
        return PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'firmas', 'code' => 'DIR_GH'],
            [
                'name' => 'Directora de GH',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }

    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    private function seedDesvinculacionTemplates(int $count): array
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => config('employee_ficha.word_document_type_codes.desvinculacion')],
            [
                'name' => 'Desvinculacion',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $templates = [];

        for ($i = 1; $i <= $count; $i++) {
            $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-masivos-'.$i.'.docx';
            Storage::disk('local')->put($path, $this->makeDocxBinary('[NOMBRE] [CEDULA]'));

            $templates[] = TerminationLetterDocumentTemplate::query()->create([
                'word_document_type_id' => $type->id,
                'label' => 'Plantilla Masivos '.$i,
                'sort_order' => $i,
                'template_path' => $path,
            ]);
        }

        return $templates;
    }

    private function makeDocxBinary(string $content): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'letter-bin-');
        $path = $temp.'.docx';
        @unlink($temp);

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText($content);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    private function createActiveEntry(string $fullName = 'Empleado Masivos Test'): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-MAS-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '10'.random_int(10000000, 99999999),
            'hired_full_name' => $fullName,
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

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $requisition->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1500000,
            'contract_type_name' => 'Termino indefinido',
            'work_center_name' => 'Bogota',
            'opened_by' => $mover->id,
        ]);

        foreach (config('employee_ficha.termination_cause_defaults', []) as $index => $row) {
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }

            PayrollCatalogItem::query()->firstOrCreate(
                ['catalog_type' => 'termination_cause', 'code' => $code],
                [
                    'name' => (string) ($row['name'] ?? $code),
                    'sort_order' => (int) ($row['sort_order'] ?? ($index + 1)),
                    'is_active' => true,
                ],
            );
        }

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
