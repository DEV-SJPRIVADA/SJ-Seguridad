<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
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
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class ContratacionQuickLetterTest extends TestCase
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

    public function test_quick_form_is_available_for_pending_entry(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry();

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry))
            ->assertOk()
            ->assertSee('Carta de contratación')
            ->assertSee('permanece en Pendientes');
    }

    public function test_quick_form_forbidden_without_manage_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo('ficha_empleados.view');
        $entry = $this->createPendingEntry();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry))
            ->assertForbidden();
    }

    public function test_quick_form_not_found_when_already_in_ficha(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry();
        $entry->update([
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry))
            ->assertNotFound();
    }

    public function test_generate_quick_saves_minimal_profile_keeps_pending_and_downloads_docx(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry();
        $city = $this->seedCity();
        $signatory = $this->seedSignatory();
        $template = $this->seedContratacionTemplate(
            '${NOMBRE_COMPLETO} ${DOCUMENTO} ${LUGAR_NACIMIENTO} ${DIRECCION} ${CIUDAD_RESIDENCIA} ${TELEFONO} ${EMAIL} ${FECHA_NACIMIENTO} ${SALARIO} ${FECHA_INGRESO} ${CIUDAD_REQUISICION} ${CARGO}',
        );

        $response = $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.contratacion.quick.generate', $entry), [
                'full_name' => 'Pérez Gómez Ana María',
                'document_number' => $entry->hired_document,
                'birth_place' => 'Medellín',
                'address' => 'Calle 10 #20-30',
                'residence_city_code' => $city->code,
                'phone' => '3001234567',
                'email' => 'ana.perez@example.com',
                'birth_date' => '1995-04-15',
                'salary' => '1.500.000',
                'hire_date' => now()->toDateString(),
                'position_name' => 'Vigilante',
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ]);

        $response->assertOk();
        $response->assertDownload();

        $entry->refresh();
        $this->assertNull($entry->moved_to_ficha_at);
        $this->assertSame('Pérez Gómez Ana María', $entry->hired_full_name);

        $profile = $entry->profile;
        $this->assertNotNull($profile);
        $this->assertSame('Pérez Gómez Ana María', $profile->full_name);
        $this->assertSame('Medellín', $profile->birth_place);
        $this->assertSame('Calle 10 #20-30', $profile->address);
        $this->assertSame($city->code, $profile->residence_city_code);
        $this->assertSame($city->name, $profile->residence_city_name);
        $this->assertSame('3001234567', $profile->phone);
        $this->assertSame('ana.perez@example.com', $profile->email);
        $this->assertSame('Vigilante', $profile->position_name);
        $this->assertEquals(1500000.0, (float) $profile->salary);

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
            ->first();

        $this->assertNotNull($period);
        $this->assertSame('docx', $period->termination_letter_type);
        $this->assertNotNull($period->termination_letter_path);
        Storage::disk('local')->assertExists((string) $period->termination_letter_path);

        $text = $this->extractDocxText(Storage::disk('local')->path((string) $period->termination_letter_path));
        $this->assertStringContainsString('Pérez Gómez Ana María', $text);
        $this->assertStringContainsString((string) $entry->hired_document, $text);
        $this->assertStringContainsString('Medellín', $text);
        $this->assertStringContainsString('Calle 10 #20-30', $text);
        $this->assertStringContainsString($city->name, $text);
        $this->assertStringContainsString('3001234567', $text);
        $this->assertStringContainsString('ana.perez@example.com', $text);
        $this->assertStringContainsString('Vigilante', $text);
        $this->assertStringNotContainsString('${NOMBRE_COMPLETO}', $text);
        $this->assertStringNotContainsString('${CARGO}', $text);

        $audit = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'contratacion_letter_pack')
            ->where('action', 'generate_quick')
            ->where('auditable_id', $period->id)
            ->firstOrFail();

        $this->assertTrue($audit->metadata['still_pending']);
        $this->assertSame([$template->id], $audit->metadata['template_ids']);
    }

    public function test_rehire_pending_can_open_form_and_generate_quick_letter(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry();
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry->update([
            'moved_to_ficha_at' => now()->subMonths(2),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1200000,
        ]);

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $entry->personal_requisition_id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => now()->subYear()->toDateString(),
            'termination_date' => now()->subMonth()->toDateString(),
            'is_rehireable' => true,
            'opened_by' => $mover->id,
            'closed_by' => $mover->id,
        ]);

        $profile->update([
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => now()->subMonth()->toDateString(),
        ]);

        $entry->update([
            'moved_to_ficha_at' => null,
            'moved_to_ficha_by' => null,
        ]);
        $entry->refresh()->load('profile');
        $this->assertTrue($entry->isRehirePending());

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry))
            ->assertOk()
            ->assertSee('reingreso', false)
            ->assertSee('Gestionar reingreso', false);

        $datatable = $this->actingAs($manager)
            ->getJson(route('gestion-humana.ficha-empleados.employees.datatable', [
                'estado' => 'pendientes',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]));
        $datatable->assertOk();
        $actionsHtml = collect($datatable->json('data'))
            ->map(fn (array $row): string => (string) ($row[8] ?? ''))
            ->implode(' ');
        $this->assertStringContainsString(
            route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry),
            $actionsHtml,
        );

        $city = $this->seedCity();
        $signatory = $this->seedSignatory();
        $template = $this->seedContratacionTemplate('${NOMBRE_COMPLETO} ${DOCUMENTO} ${CARGO}');

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.contratacion.quick.generate', $entry), [
                'full_name' => $entry->hired_full_name,
                'document_number' => $entry->hired_document,
                'birth_place' => 'Cali',
                'address' => 'Calle 1 #2-3',
                'residence_city_code' => $city->code,
                'phone' => '3009998877',
                'email' => 'reingreso@example.com',
                'birth_date' => '1990-01-10',
                'salary' => '1.800.000',
                'hire_date' => now()->toDateString(),
                'position_name' => 'Vigilante',
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk()
            ->assertDownload();

        $entry->refresh()->load('profile');
        $this->assertNull($entry->moved_to_ficha_at);
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
        $this->assertNull($entry->profile?->termination_date);

        $activePeriod = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
            ->first();

        $this->assertNotNull($activePeriod);
        $this->assertSame(2, $activePeriod->sequence);
        $this->assertNotNull($activePeriod->termination_letter_path);
    }

    public function test_quick_letter_reattaches_existing_profile_when_pending_entry_has_no_profile(): void
    {
        $manager = $this->managerUser();
        $mover = User::factory()->create(['must_change_password' => false]);
        $oldEntry = $this->createPendingEntry('900111001', 'Pérez Gómez Ana');

        $oldEntry->update([
            'moved_to_ficha_at' => now()->subMonths(2),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $oldEntry->id,
            'document_number' => $oldEntry->hired_document,
            'full_name' => $oldEntry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => now()->subMonth()->toDateString(),
            'hire_date' => now()->subYear()->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1200000,
        ]);

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $oldEntry->id,
            'personal_requisition_id' => $oldEntry->personal_requisition_id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => now()->subYear()->toDateString(),
            'termination_date' => now()->subMonth()->toDateString(),
            'is_rehireable' => true,
            'opened_by' => $mover->id,
            'closed_by' => $mover->id,
        ]);

        // Pendiente nuevo sin perfil (reingreso que creó otra entrada en lugar de reasignar).
        $newEntry = $this->createPendingEntry('900111001', 'Pérez Gómez Ana');

        $city = $this->seedCity();
        $signatory = $this->seedSignatory();
        $template = $this->seedContratacionTemplate('${NOMBRE_COMPLETO} ${DOCUMENTO}');

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.contratacion.quick.generate', $newEntry), [
                'full_name' => $newEntry->hired_full_name,
                'document_number' => $newEntry->hired_document,
                'birth_place' => 'Cali',
                'address' => 'Calle 1 #2-3',
                'residence_city_code' => $city->code,
                'phone' => '3009998877',
                'email' => 'reingreso-dup@example.com',
                'birth_date' => '1990-01-10',
                'salary' => '1.800.000',
                'hire_date' => now()->toDateString(),
                'position_name' => 'Vigilante',
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk()
            ->assertDownload();

        $profile->refresh();
        $this->assertSame($newEntry->id, $profile->personal_requisition_ficha_entry_id);
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $profile->employment_status);
        $this->assertNull($profile->termination_date);

        $this->assertSame(0, PersonalRequisitionFichaEntry::query()->whereKey($oldEntry->id)->count());

        $activePeriod = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $newEntry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
            ->first();

        $this->assertNotNull($activePeriod);
        $this->assertSame(2, $activePeriod->sequence);
    }

    public function test_quick_letter_rejects_document_owned_by_active_in_ficha_employee(): void
    {
        $manager = $this->managerUser();
        $mover = User::factory()->create(['must_change_password' => false]);
        $owner = $this->createPendingEntry('900222003', 'Activo En Ficha');

        $owner->update([
            'moved_to_ficha_at' => now()->subMonth(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $owner->id,
            'document_number' => $owner->hired_document,
            'full_name' => $owner->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1200000,
        ]);

        $orphanPending = $this->createPendingEntry('900222003', 'Activo En Ficha');
        $city = $this->seedCity();
        $signatory = $this->seedSignatory();
        $template = $this->seedContratacionTemplate('${DOCUMENTO}');

        $this->actingAs($manager)
            ->from(route('gestion-humana.ficha-empleados.employees.contratacion.quick', $orphanPending))
            ->post(route('gestion-humana.ficha-empleados.employees.contratacion.quick.generate', $orphanPending), [
                'full_name' => $orphanPending->hired_full_name,
                'document_number' => $orphanPending->hired_document,
                'birth_place' => 'Cali',
                'address' => 'Calle 1 #2-3',
                'residence_city_code' => $city->code,
                'phone' => '3009998877',
                'email' => 'activo@example.com',
                'birth_date' => '1990-01-10',
                'salary' => '1.800.000',
                'hire_date' => now()->toDateString(),
                'position_name' => 'Vigilante',
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('document_number');
    }

    public function test_pending_datatable_shows_quick_letter_action(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry();

        $response = $this->actingAs($manager)
            ->getJson(route('gestion-humana.ficha-empleados.employees.datatable', [
                'estado' => 'pendientes',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $html = collect($response->json('data'))
            ->map(fn (array $row): string => (string) ($row[8] ?? ''))
            ->implode(' ');

        $this->assertStringContainsString(
            route('gestion-humana.ficha-empleados.employees.contratacion.quick', $entry),
            $html,
        );
        $this->assertStringContainsString('Carta de contratación', $html);
        $this->assertStringNotContainsString('cursos-catalogo-page__icon-btn--success', $html);
    }

    public function test_pending_datatable_marks_letter_icon_when_already_generated(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry();

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'opened_by' => $manager->id,
            'hire_date' => now()->toDateString(),
            'termination_letter_path' => 'ficha-empleados/contratacion-letters/1/carta.docx',
            'termination_letter_type' => 'docx',
        ]);

        $response = $this->actingAs($manager)
            ->getJson(route('gestion-humana.ficha-empleados.employees.datatable', [
                'estado' => 'pendientes',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $html = collect($response->json('data'))
            ->map(fn (array $row): string => (string) ($row[8] ?? ''))
            ->implode(' ');

        $this->assertStringContainsString('cursos-catalogo-page__icon-btn--success', $html);
        $this->assertStringContainsString('Carta generada (volver a generar)', $html);
    }

    private function managerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('ficha_empleados.manage');

        return $user;
    }

    private function createPendingEntry(?string $document = null, ?string $fullName = null): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-QUICK-'.uniqid());
        $creator = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document ?: '20'.random_int(10000000, 99999999),
            'hired_full_name' => $fullName ?: 'Pendiente Carta Test',
            'moved_to_ficha_at' => null,
            'created_by' => $creator->id,
        ]);
    }

    private function seedCity(): PayrollCatalogItem
    {
        return PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'city', 'code' => '11001'],
            [
                'name' => 'Bogotá D.C.',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
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

    private function seedContratacionTemplate(string $content): TerminationLetterDocumentTemplate
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => config('employee_ficha.word_document_type_codes.contratacion')],
            [
                'name' => 'Contratacion',
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-quick.docx';
        Storage::disk('local')->put($path, $this->makeDocxBinary($content));

        return TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => 'Plantilla carta rapida',
            'sort_order' => 1,
            'template_path' => $path,
        ]);
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

    private function extractDocxText(string $absolutePath): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($absolutePath));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
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
            'base_salary' => 1500000,
            'hiring_date' => now()->toDateString(),
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
