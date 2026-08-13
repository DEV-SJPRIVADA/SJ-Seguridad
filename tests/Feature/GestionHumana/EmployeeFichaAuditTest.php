<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\EmployeeFichaMasivosPayload;
use Tests\TestCase;

class EmployeeFichaAuditTest extends TestCase
{
    use EmployeeFichaMasivosPayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        $this->seedFe028CatalogFixtures();
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_store_promote_writes_promote_audit_event(): void
    {
        $manager = $this->managerUser();
        $requisition = $this->createRequisition('REQ-AUD-PROMO-01');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900111001',
            'hired_full_name' => 'Promote Audit Test',
        ]);

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), array_merge(
            $this->masivosCorePayload(),
            [
                'ficha_entry_id' => $entry->id,
                'hired_document' => '900111001',
                'hired_full_name' => 'Promote Audit Test',
                'document_type' => 'C',
            ],
        ))->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'ficha_entry')
            ->where('action', 'promote')
            ->where('auditable_id', $entry->id)
            ->firstOrFail();

        $this->assertSame('gestion_humana', $log->area);
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame('900111001', $log->metadata['hired_document']);
        $this->assertSame('waiting_list', $log->metadata['source']);
        $this->assertSame($requisition->id, $log->metadata['requisition_id']);
    }

    public function test_store_manual_create_writes_create_audit_event(): void
    {
        $manager = $this->managerUser();

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), array_merge(
            $this->masivosCorePayload(['sex' => 'F', 'salary' => '1800000']),
            [
                'hired_document' => '900222002',
                'hired_full_name' => 'Manual Audit Test',
                'document_type' => 'C',
            ],
        ))->assertRedirect();

        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', '900222002')
            ->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'ficha_entry')
            ->where('action', 'create')
            ->where('auditable_id', $entry->id)
            ->firstOrFail();

        $this->assertSame('gestion_humana', $log->area);
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame('900222002', $log->metadata['hired_document']);
        $this->assertSame('manual', $log->metadata['source']);
    }

    public function test_terminate_ficha_writes_status_change_audit_event(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createInFichaEntry('900333003', 'Status Change Audit');
        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '900333003',
            'full_name' => 'Status Change Audit',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
        ]);
        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $entry->personal_requisition_id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
            'opened_by' => $terminator->id,
        ]);

        $this->actingAs($terminator)->post(
            route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry),
            [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ],
        )->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'ficha_profile')
            ->where('action', 'status_change')
            ->where('auditable_id', $profile->id)
            ->firstOrFail();

        $this->assertSame(['employment_status' => EmployeeFichaProfile::STATUS_ACTIVO], $log->old_values);
        $this->assertSame(['employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO], $log->new_values);
        $this->assertSame('900333003', $log->metadata['document_number']);
    }

    public function test_import_writes_single_summary_audit_event(): void
    {
        $manager = $this->managerUser();
        $path = $this->makeImportSpreadsheet([
            'cedula' => '900444004',
            'nombre' => 'Import Audit Test',
            'fecha_ingreso' => '2026-01-15',
        ]);

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.import'), [
            'import_file' => new UploadedFile($path, 'import.xlsx', null, null, true),
        ])->assertRedirect();

        $this->assertSame(1, AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'import')
            ->where('action', 'profiles')
            ->count());

        $log = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'import')
            ->where('action', 'profiles')
            ->firstOrFail();

        $this->assertSame('gestion_humana', $log->area);
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame(1, $log->metadata['imported']);
        $this->assertSame(0, $log->metadata['updated']);
        $this->assertSame(0, $log->metadata['skipped']);
        $this->assertSame(0, $log->metadata['empty_rows']);
    }

    public function test_export_excel_writes_masivos_excel_audit_event(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createInFichaEntry('900555005', 'Export Audit Test');
        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '900555005',
            'full_name' => 'Export Audit Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.export'))
            ->assertOk();

        $log = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'export')
            ->where('action', 'masivos_excel')
            ->firstOrFail();

        $this->assertSame('gestion_humana', $log->area);
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame(1, $log->metadata['row_count']);
        $this->assertArrayNotHasKey('date_range', $log->metadata);
    }

    private function managerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['ficha_empleados.view', 'ficha_empleados.manage']);

        return $user;
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['ficha_empleados.view', 'ficha_empleados.manage', 'ficha_empleados.terminate']);

        return $user;
    }

    private function createInFichaEntry(string $document, string $name): PersonalRequisitionFichaEntry
    {
        $mover = User::factory()->create(['must_change_password' => false]);
        $requisition = $this->createRequisition('REQ-AUD-'.substr($document, -4));
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'hired_full_name' => $name,
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        return $entry->fresh();
    }

    private function createRequisition(string $code): PersonalRequisition
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisition::query()->create([
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
            'required_profile' => 'Perfil audit ficha empleados.',
            'service_structure' => 'Turno audit ficha empleados.',
            'cost_center' => 'CC-AUD',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function makeImportSpreadsheet(array $row): string
    {
        $columns = array_keys(config('employee_ficha.import_columns'));
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $key) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $key);
            $sheet->setCellValue($col.'2', $key);
        }

        foreach ($columns as $index => $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'3', $row[$key]);
        }

        $path = tempnam(sys_get_temp_dir(), 'ficha-audit-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
