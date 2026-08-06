<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Import\ImportFailureReportManager;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportFailureReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_commercial_import_provides_downloadable_failure_report(): void
    {
        $manager = $this->matrizManager();
        $path = $this->makeCommercialSpreadsheet([
            'nit' => '900777001',
            'client_name' => 'Cliente Reporte',
            'portfolio' => 'invalido',
        ]);

        $file = new UploadedFile($path, 'matriz.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($manager)
            ->post(route('comercial.matriz.clients.import'), ['import_file' => $file]);

        $response->assertRedirect(route('comercial.matriz.clients.index'));
        $importResult = session('import_result');
        $this->assertIsArray($importResult);
        $this->assertNotEmpty($importResult['report_token'] ?? null);
        $this->assertGreaterThan(0, $importResult['failures_count'] ?? 0);

        $download = $this->actingAs($manager)
            ->get(route('comercial.matriz.clients.import-report', $importResult['report_token']));

        $download->assertOk();
        $download->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_employee_import_provides_downloadable_failure_report(): void
    {
        $manager = $this->fichaManager();
        $failures = [[
            'row' => 3,
            'identifier' => '123456789',
            'identifier_label' => 'Cedula',
            'severity' => 'error',
            'reason' => 'Error de prueba',
            'raw' => ['cedula' => '123456789', 'nombre' => 'Test'],
        ]];

        $token = app(ImportFailureReportManager::class)->store(
            $manager,
            'employee_ficha',
            $failures,
            ['Filas con error' => 1],
            'Ficha empleados',
            array_keys(config('employee_ficha.import_columns', [])),
            'reporte_importacion_ficha_empleados',
        );

        $this->assertNotEmpty($token);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.import-report', $token))
            ->assertOk();
    }

    public function test_import_report_download_rejects_foreign_token(): void
    {
        $owner = $this->matrizManager();
        $other = User::factory()->create(['must_change_password' => false, 'area_key' => 'comercial']);
        $other->assignRole('usuario');
        $other->givePermissionTo('comercial.matriz.manage');

        $path = $this->makeCommercialSpreadsheet([
            'nit' => '900777002',
            'client_name' => 'Otro',
            'portfolio' => 'invalido',
        ]);

        $file = new UploadedFile($path, 'matriz.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($owner)->post(route('comercial.matriz.clients.import'), ['import_file' => $file]);
        $token = session('import_result.report_token');

        $this->actingAs($other)
            ->get(route('comercial.matriz.clients.import-report', $token))
            ->assertForbidden();
    }

    private function matrizManager(): User
    {
        $user = User::factory()->create(['must_change_password' => false, 'area_key' => 'comercial']);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.manage');

        return $user;
    }

    private function fichaManager(): User
    {
        $user = User::factory()->create(['must_change_password' => false, 'area_key' => 'gestion_humana']);
        $user->assignRole('usuario');
        $user->givePermissionTo('ficha_empleados.manage');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function makeCommercialSpreadsheet(array $row): string
    {
        $columns = array_keys(config('commercial_matrix.import_columns'));
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(config('commercial_matrix.sheet_name'));

        foreach ($columns as $index => $key) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $key);
            $sheet->setCellValue($col.'2', $key);
        }

        foreach ($columns as $index => $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'3', $row[$key]);
        }

        $path = tempnam(sys_get_temp_dir(), 'commercial-fail-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
