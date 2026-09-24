<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AcreditacionesImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-23', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_import_template_requires_edit(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados.import-template'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.acreditados.import-template'))
            ->assertOk();
    }

    public function test_import_template_has_keys_and_excludes_estado(): void
    {
        $editor = $this->editorUser();

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.acreditados.import-template'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'acr-tpl-');
        file_put_contents($temp, $response->streamedContent());

        $sheet = IOFactory::load($temp)->getActiveSheet();
        $keys = array_keys(config('acreditaciones.import.columns'));

        foreach ($keys as $i => $key) {
            $this->assertSame(
                $key,
                (string) $sheet->getCell([$i + 1, 1])->getValue(),
            );
        }

        $this->assertNotContains('estado', $keys);
        $this->assertContains('document_number', $keys);
        $this->assertContains('cargo_apo', $keys);

        if (is_file($temp)) {
            unlink($temp);
        }
    }

    public function test_import_upserts_by_cedula_and_cargo_apo(): void
    {
        $editor = $this->editorUser();
        $this->activeCargo('VIGILANTE');
        $this->createFicha('100', 'Nombre Ficha Cien');
        $this->createFicha('200', 'Maria Ficha');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '100',
            'full_name' => 'Viejo Nombre',
            'cargo' => 'VIEJO',
            'cargo_apo' => 'VIGILANTE',
            'vigencia_acr' => '2027-01-01',
            'fecha_solicitud' => null,
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'observaciones' => 'antes',
        ]);

        $path = $this->makeImportFile([
            ['100', 'Ignorado Excel', 'GUARDA', 'VIGILANTE', '2027-06-01', '', 'actualizado'],
            ['200', 'Ignorado', 'GUARDA', 'VIGILANTE', '2027-12-01', '', 'nuevo'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.import'), [
                'import_file' => new UploadedFile($path, 'acreditados.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'))
            ->assertSessionHas('status');

        $this->assertSame(1, AcreditacionAcreditado::query()
            ->where('document_number', '100')
            ->where('cargo_apo', 'VIGILANTE')
            ->count());

        $updated = AcreditacionAcreditado::query()
            ->where('document_number', '100')
            ->where('cargo_apo', 'VIGILANTE')
            ->firstOrFail();

        $this->assertSame('Nombre Ficha Cien', $updated->full_name);
        $this->assertSame('GUARDA', $updated->cargo);
        $this->assertSame('2027-06-01', optional($updated->vigencia_acr)?->format('Y-m-d'));
        $this->assertSame('actualizado', $updated->observaciones);
        $this->assertSame(AcreditacionAcreditado::ESTADO_ACREDITADO, $updated->estado);

        $this->assertDatabaseHas('acreditacion_acreditados', [
            'document_number' => '200',
            'full_name' => 'Maria Ficha',
            'cargo_apo' => 'VIGILANTE',
            'cargo' => 'GUARDA',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);
    }

    public function test_import_accepts_blank_and_en_proceso_vigencia(): void
    {
        $editor = $this->editorUser();
        $this->activeCargo('VIGILANTE');
        $this->createFicha('310', 'Sin Vigencia Vacia');
        $this->createFicha('311', 'Sin Vigencia Texto');

        $path = $this->makeImportFile([
            ['310', 'Ignorado', 'GUARDA', 'VIGILANTE', '', '', 'obs vacia'],
            ['311', 'Ignorado', 'GUARDA', 'VIGILANTE', 'en proceso', '', 'obs texto'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.import'), [
                'import_file' => new UploadedFile($path, 'acreditados.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'))
            ->assertSessionHas('status');

        $failures = session('import_failures', []);
        $this->assertSame([], $failures, json_encode($failures, JSON_UNESCAPED_UNICODE));

        $blank = AcreditacionAcreditado::query()
            ->where('document_number', '310')
            ->firstOrFail();
        $this->assertNull($blank->vigencia_acr);
        $this->assertNull($blank->fecha_solicitud);
        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $blank->estado);
        $this->assertSame('obs vacia', $blank->observaciones);

        $texto = AcreditacionAcreditado::query()
            ->where('document_number', '311')
            ->firstOrFail();
        $this->assertNull($texto->vigencia_acr);
        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $texto->estado);
        $this->assertSame('obs texto', $texto->observaciones);
    }

    public function test_import_fails_without_ficha_and_invalid_vigencia_text(): void
    {
        $editor = $this->editorUser();
        $this->activeCargo('VIGILANTE');
        $this->createFicha('300', 'Con Ficha Vigencia Invalida');

        $path = $this->makeImportFile([
            ['999', 'Sin Ficha', 'GUARDA', 'VIGILANTE', '2027-01-01', '', ''],
            ['300', 'Con Ficha', 'GUARDA', 'VIGILANTE', 'texto-invalido', '', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.import'), [
                'import_file' => new UploadedFile($path, 'acreditados.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'))
            ->assertSessionHas('import_failures')
            ->assertSessionHas('import_report_token');

        $failures = session('import_failures');
        $this->assertCount(2, $failures);
        $this->assertStringContainsString(
            'La cedula no existe en Ficha empleados.',
            (string) ($failures[0]['reason'] ?? ''),
        );
        $this->assertStringContainsString(
            'VIGEN.ACR no es una fecha válida',
            (string) ($failures[1]['reason'] ?? ''),
        );

        $this->assertDatabaseMissing('acreditacion_acreditados', [
            'document_number' => '999',
        ]);
        $this->assertDatabaseMissing('acreditacion_acreditados', [
            'document_number' => '300',
        ]);

        $token = session('import_report_token');
        $this->assertNotEmpty($token);
        $this->assertTrue(Cache::has('acreditaciones_import_report_'.$token));

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.acreditados.import-report', $token))
            ->assertOk();
    }

    public function test_viewer_forbidden_on_import_and_report(): void
    {
        $viewer = $this->viewerUser();
        $this->activeCargo('VIGILANTE');
        $this->createFicha('400', 'Viewer Block');

        $path = $this->makeImportFile([
            ['400', 'X', 'GUARDA', 'VIGILANTE', '2027-01-01', '', ''],
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.acreditados.import'), [
                'import_file' => new UploadedFile($path, 'acreditados.xlsx', null, null, true),
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados.import-report', 'fake-token'))
            ->assertForbidden();
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function makeImportFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $keys = array_keys(config('acreditaciones.import.columns'));
        $labels = array_values(config('acreditaciones.import.columns'));

        foreach ($keys as $i => $key) {
            $col = $i + 1;
            $sheet->setCellValue([$col, 1], $key);
            $sheet->setCellValue([$col, 2], $labels[$i]);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 3], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'acr_import_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function createFicha(string $documentNumber, string $fullName): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
        ]);
    }

    private function activeCargo(string $cargoApo): AcreditacionCargo
    {
        return AcreditacionCargo::factory()->create([
            'cargo_apo' => $cargoApo,
            'is_active' => true,
        ]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}
