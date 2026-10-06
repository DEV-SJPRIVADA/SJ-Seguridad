<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\EmployeeTerminationFollowup;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\ReportesNovedadesRetiro;
use App\Models\User;
use App\Services\GestionHumana\HistoricalTerminationImportService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HistoricalTerminationImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_import_creates_followup_and_retiro_without_letter(): void
    {
        $entry = $this->makeFichaEntry('10999001', 'Empleado Historico Uno', '2024-01-10', EmployeeFichaProfile::STATUS_ACTIVO);
        $path = $this->writeNovedadesXlsx([
            [
                'doc' => '10999001',
                'name' => 'Empleado Historico Uno',
                'cargo' => 'GUARDA',
                'cause' => 'RENUNCIA',
                'created' => '2025-06-02',
                'retiro' => '2025-06-01',
                'checks' => [true, true, false, false, false, false, false, false],
                'payroll' => '2025-06-05',
                'obs' => 'Histórico import',
            ],
        ]);

        $stats = app(HistoricalTerminationImportService::class)->import($path, dryRun: false, userId: 1);

        $this->assertSame(1, $stats['created']);
        $this->assertSame([], $stats['errors']);

        $followup = EmployeeTerminationFollowup::query()->where('document_number', '10999001')->first();
        $this->assertNotNull($followup);
        $this->assertFalse($followup->letter_generated);
        $this->assertTrue($followup->check_orden_examenes);
        $this->assertTrue($followup->check_enviado);
        $this->assertSame('2025-06-01', $followup->termination_date?->toDateString());
        $this->assertSame('RENUNCIA', $followup->termination_cause_code);

        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $entry->profile?->employment_status);

        $this->assertDatabaseHas('reportes_novedades_retiros', [
            'document_number' => '10999001',
            'employee_termination_followup_id' => $followup->id,
        ]);
    }

    public function test_import_keeps_activo_when_hire_after_termination(): void
    {
        $entry = $this->makeFichaEntry('10999002', 'Reingreso Posterior', '2025-08-01', EmployeeFichaProfile::STATUS_ACTIVO);
        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => '2025-08-01',
            'opened_by' => 1,
            'position_name' => 'GUARDA',
        ]);

        $path = $this->writeNovedadesXlsx([
            [
                'doc' => '10999002',
                'name' => 'Reingreso Posterior',
                'cargo' => 'GUARDA',
                'cause' => 'TERMINACION DE CONTRATO',
                'created' => '2025-05-10',
                'retiro' => '2025-05-09',
                'checks' => [false, false, false, false, false, false, false, false],
                'payroll' => null,
                'obs' => null,
            ],
        ]);

        $stats = app(HistoricalTerminationImportService::class)->import($path, dryRun: false, userId: 1);

        $this->assertSame(1, $stats['created_keep_activo']);
        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
        $this->assertNotNull($this->periodServiceActive($entry));
        $this->assertTrue(
            EmployeeTerminationFollowup::query()->where('document_number', '10999002')->exists()
        );
    }

    public function test_import_updates_existing_followup(): void
    {
        $entry = $this->makeFichaEntry('10999003', 'Actualizar Checks', '2024-02-01', EmployeeFichaProfile::STATUS_DESVINCULADO);
        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => '2024-02-01',
            'termination_date' => '2025-07-01',
            'opened_by' => 1,
            'closed_by' => 1,
            'position_name' => 'GUARDA',
        ]);
        EmployeeTerminationFollowup::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'employee_ficha_employment_period_id' => $period->id,
            'document_number' => '10999003',
            'full_name' => 'Actualizar Checks',
            'termination_date' => '2025-07-01',
            'registered_at' => '2025-07-02 12:00:00',
            'letter_generated' => false,
            'check_enviado' => false,
            'created_by' => 1,
        ]);

        $path = $this->writeNovedadesXlsx([
            [
                'doc' => '10999003',
                'name' => 'Actualizar Checks',
                'cargo' => 'ESCOLTA',
                'cause' => 'RENUNCIA',
                'created' => '2025-07-02',
                'retiro' => '2025-07-01',
                'checks' => [true, true, true, false, false, false, false, false],
                'payroll' => '2025-07-10',
                'obs' => 'Actualizado',
            ],
        ]);

        $stats = app(HistoricalTerminationImportService::class)->import($path, dryRun: false, userId: 1);
        $this->assertSame(1, $stats['updated']);

        $followup = EmployeeTerminationFollowup::query()->where('document_number', '10999003')->first();
        $this->assertTrue($followup?->check_enviado);
        $this->assertSame('2025-07-10', $followup?->payroll_delivered_at?->toDateString());
        $this->assertSame('Actualizado', $followup?->termination_notes);
    }

    public function test_dry_run_does_not_write(): void
    {
        $this->makeFichaEntry('10999004', 'Dry Run', '2024-01-01', EmployeeFichaProfile::STATUS_ACTIVO);
        $path = $this->writeNovedadesXlsx([
            [
                'doc' => '10999004',
                'name' => 'Dry Run',
                'cargo' => 'GUARDA',
                'cause' => 'RENUNCIA',
                'created' => '2025-06-01',
                'retiro' => '2025-06-01',
                'checks' => [false, false, false, false, false, false, false, false],
                'payroll' => null,
                'obs' => null,
            ],
        ]);

        $stats = app(HistoricalTerminationImportService::class)->import($path, dryRun: true, userId: 1);
        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, EmployeeTerminationFollowup::query()->count());
        $this->assertSame(0, ReportesNovedadesRetiro::query()->count());
    }

    public function test_http_import_requires_seguimientos_edit(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $path = $this->writeNovedadesXlsx([]);
        $upload = new UploadedFile($path, 'novedades.xlsx', null, null, true);

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.import-historico'), [
                'import_file' => $upload,
                'dry_run' => 1,
            ])
            ->assertForbidden();
    }

    public function test_http_import_dry_run_and_real_load(): void
    {
        $this->makeFichaEntry('10999005', 'Via Http', '2024-03-01', EmployeeFichaProfile::STATUS_ACTIVO);
        $editor = User::factory()->create(['must_change_password' => false]);
        $editor->givePermissionTo([
            'desvinculaciones.view',
            'desvinculaciones.seguimientos.edit',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $path = $this->writeNovedadesXlsx([
            [
                'doc' => '10999005',
                'name' => 'Via Http',
                'cargo' => 'GUARDA',
                'cause' => 'RENUNCIA',
                'created' => '2025-06-01',
                'retiro' => '2025-06-01',
                'checks' => [true, false, false, false, false, false, false, false],
                'payroll' => null,
                'obs' => null,
            ],
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.import-historico'), [
                'import_file' => new UploadedFile($path, 'novedades.xlsx', null, null, true),
                'dry_run' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('stats.created', 1);

        $this->assertSame(0, EmployeeTerminationFollowup::query()->count());

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.import-historico'), [
                'import_file' => new UploadedFile($path, 'novedades.xlsx', null, null, true),
                'dry_run' => 0,
            ])
            ->assertStatus(422);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.import-historico'), [
                'import_file' => new UploadedFile($path, 'novedades.xlsx', null, null, true),
                'dry_run' => 0,
                'confirm_import' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('dry_run', false)
            ->assertJsonPath('stats.created', 1);

        $this->assertTrue(
            EmployeeTerminationFollowup::query()->where('document_number', '10999005')->exists()
        );
    }

    private function periodServiceActive(PersonalRequisitionFichaEntry $entry): ?EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
            ->first();
    }

    private function makeFichaEntry(
        string $document,
        string $name,
        string $hireDate,
        string $status,
    ): PersonalRequisitionFichaEntry {
        $user = User::factory()->create(['must_change_password' => false]);
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'hired_document' => $document,
            'hired_full_name' => $name,
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $user->id,
            'created_by' => $user->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $document,
            'full_name' => $name,
            'hire_date' => $hireDate,
            'employment_status' => $status,
            'termination_date' => $status === EmployeeFichaProfile::STATUS_DESVINCULADO ? '2025-06-01' : null,
        ]);

        return $entry->fresh(['profile']);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeNovedadesXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NOVEDADES');
        $headers = [
            'A' => 'No', 'B' => 'CEDULA', 'C' => 'NOMBRE  Y APELLIDOS', 'D' => 'CARGO',
            'E' => 'TIPO DESVINCULACIÓN', 'F' => 'FECHA DE CREACION', 'G' => 'FECHA DE RET',
            'H' => 'ORDEN EXAMENES', 'I' => 'ENVIADO', 'J' => 'CONTROL ROLL', 'K' => 'RETIRO ARL',
            'L' => 'RETIRO CESANTIAS', 'M' => 'RECIBIDO', 'N' => 'PAZ Y SALVO', 'O' => 'REPORTE NOVED',
            'P' => 'OK TODO', 'Q' => 'FECHA ENTREGADO NM', 'X' => 'OBSERVACIONES',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col.'1', $label);
        }

        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $sheet->setCellValue('A'.$r, $i + 1);
            $sheet->setCellValue('B'.$r, $row['doc']);
            $sheet->setCellValue('C'.$r, $row['name']);
            $sheet->setCellValue('D'.$r, $row['cargo']);
            $sheet->setCellValue('E'.$r, $row['cause']);
            $sheet->setCellValue('F'.$r, $row['created']);
            $sheet->setCellValue('G'.$r, $row['retiro']);
            $checks = $row['checks'];
            foreach (['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'] as $idx => $col) {
                $sheet->setCellValue($col.$r, $checks[$idx] ? 'TRUE' : 'FALSE');
            }
            if ($row['payroll']) {
                $sheet->setCellValue('Q'.$r, $row['payroll']);
            }
            if ($row['obs']) {
                $sheet->setCellValue('X'.$r, $row['obs']);
            }
        }

        $path = storage_path('framework/testing/novedades-import-'.uniqid().'.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
